<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Report;
use App\Models\ServiceRequest;
use App\Models\Quotation;
use App\Models\Client;
use App\Models\Task;
use App\Models\Assignment;
use App\Models\Approval;
use App\Models\Department;
use App\Models\KpiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected $managementUser;
    protected $staffUser;
    protected $department;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        $permissions = [
            'view reports', 'manage service requests', 'manage quotations', 'view service requests', 'view assignments', 'manage tasks'
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p);
        }

        $adminRole = Role::findOrCreate('executive_director');
        $adminRole->givePermissionTo($permissions);

        $expertRole = Role::findOrCreate('language_expert');
        $expertRole->givePermissionTo(['view assignments', 'manage tasks']);

        // 2. Setup Department and Users
        $this->department = Department::create([
            'name' => 'Department of Translation',
            'code' => 'DT'
        ]);

        $this->managementUser = User::factory()->create([
            'name' => 'Management Director',
            'email' => 'director@msunli.edu',
            'department_id' => $this->department->id,
        ]);
        $this->managementUser->assignRole('executive_director');

        $this->staffUser = User::factory()->create([
            'name' => 'Language Expert Staff',
            'email' => 'staff@msunli.edu',
            'department_id' => $this->department->id,
        ]);
        $this->staffUser->assignRole('language_expert');
    }

    /**
     * Test index page rendering for management with all analytical dashboard aggregates.
     */
    public function test_management_can_access_global_reports_dashboard()
    {
        $this->actingAs($this->managementUser);

        // Seed some dummy requests and tasks
        $client = Client::create([
            'contact_person' => 'John Client',
            'email' => 'client@test.com',
            'organization' => 'MSU Corp'
        ]);

        $request1 = ServiceRequest::create([
            'client_id' => $client->id,
            'reference_number' => 'SR-2026-0001',
            'service_category' => 'translation',
            'title' => 'Translation Request 1',
            'description' => 'Description 1',
            'priority' => 'high',
            'status' => 'completed',
            'completed_at' => now(),
            'assigned_to' => $this->staffUser->id
        ]);

        $request2 = ServiceRequest::create([
            'client_id' => $client->id,
            'reference_number' => 'SR-2026-0002',
            'service_category' => 'editing',
            'title' => 'Editing Request 2',
            'description' => 'Description 2',
            'priority' => 'medium',
            'status' => 'pending',
            'assigned_to' => $this->staffUser->id
        ]);

        $response = $this->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->has('totals')
            ->has('byCategory')
            ->has('byStatus')
            ->where('totals.total_requests', 2)
            ->where('totals.completed_services', 1)
            ->where('isManagement', true)
        );
    }

    /**
     * Test index page role-based isolation (staff restricted scope).
     */
    public function test_staff_restricted_scope_hides_other_staff_records()
    {
        // Setup another staff member
        $otherStaff = User::factory()->create();
        $otherStaff->assignRole('language_expert');

        $client = Client::create([
            'contact_person' => 'Client A',
            'email' => 'a@test.com',
            'organization' => 'Client A Corp'
        ]);

        // ServiceRequest assigned to current staff
        ServiceRequest::create([
            'client_id' => $client->id,
            'reference_number' => 'SR-STAFF-1',
            'service_category' => 'translation',
            'title' => 'Staff Translation Request',
            'description' => 'Staff Description 1',
            'priority' => 'high',
            'status' => 'completed',
            'completed_at' => now(),
            'assigned_to' => $this->staffUser->id
        ]);

        // ServiceRequest assigned to another staff
        ServiceRequest::create([
            'client_id' => $client->id,
            'reference_number' => 'SR-STAFF-2',
            'service_category' => 'editing',
            'title' => 'Staff Editing Request',
            'description' => 'Staff Description 2',
            'priority' => 'high',
            'status' => 'completed',
            'completed_at' => now(),
            'assigned_to' => $otherStaff->id
        ]);

        // Access dashboard as standard staff
        $this->actingAs($this->staffUser);

        $response = $this->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('totals.total_requests', 1) // strictly filters to 1 assigned request
            ->where('isManagement', false)
        );
    }

    /**
     * Test persistent compilation saves database records and writes file to storage.
     */
    public function test_compile_report_persists_record_and_file()
    {
        Storage::fake('public');

        $this->actingAs($this->managementUser);

        $client = Client::create([
            'contact_person' => 'John Client',
            'email' => 'client@test.com',
            'organization' => 'MSU Corp'
        ]);

        $serviceRequest = ServiceRequest::create([
            'client_id' => $client->id,
            'reference_number' => 'SR-2026-0999',
            'service_category' => 'translation',
            'title' => 'Translation Audit Request',
            'description' => 'Audit Description',
            'priority' => 'high',
            'status' => 'pending',
            'assigned_to' => $this->managementUser->id
        ]);

        Quotation::create([
            'service_request_id' => $serviceRequest->id,
            'reference_number' => 'QT-001',
            'description' => 'Translation Quote',
            'amount' => 500.00,
            'currency' => 'USD',
            'status' => 'approved',
            'prepared_by' => $this->managementUser->id,
            'valid_until' => now()->addDays(30)
        ]);

        $response = $this->post(route('reports.store'), [
            'title' => 'Monthly Quotation Audit',
            'report_type' => 'quotations',
            'format' => 'excel',
            'filters' => [
                'service_category' => 'translation'
            ]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Confirm database record created
        $this->assertDatabaseHas('reports', [
            'title' => 'Monthly Quotation Audit',
            'report_type' => 'quotations',
            'format' => 'excel',
            'generated_by' => $this->managementUser->id
        ]);

        // Retrieve generated report and verify file is written to storage disk
        $report = Report::where('title', 'Monthly Quotation Audit')->first();
        $this->assertNotNull($report->file_path);

        $filename = basename($report->file_path);
        Storage::disk('public')->assertExists('reports/' . $filename);
    }

    /**
     * Test searching and filtering inside generated reports archive.
     */
    public function test_searching_and_filtering_generated_reports()
    {
        $this->actingAs($this->managementUser);

        // Generate custom reports in database
        Report::create([
            'title' => 'Specialized Annual Quotation Audit',
            'report_type' => 'quotations',
            'format' => 'pdf',
            'generated_by' => $this->managementUser->id,
            'file_path' => '/reports/download/report_quotations_123.pdf'
        ]);

        Report::create([
            'title' => 'Client Language Influx Metric',
            'report_type' => 'client_services',
            'format' => 'pdf',
            'generated_by' => $this->managementUser->id,
            'file_path' => '/reports/download/report_clients_123.pdf'
        ]);

        // Search by title "Annual"
        $response = $this->get(route('reports.index', ['search' => 'Annual']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->has('reports.data', 1)
            ->where('reports.data.0.title', 'Specialized Annual Quotation Audit')
        );

        // Search by type "client_services"
        $response = $this->get(route('reports.index', ['archive_type' => 'client_services']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->has('reports.data', 1)
            ->where('reports.data.0.title', 'Client Language Influx Metric')
        );
    }

    /**
     * Test dynamic live export streams.
     */
    public function test_quick_export_live_stream_csv()
    {
        $this->actingAs($this->managementUser);

        $response = $this->get(route('reports.export', [
            'format' => 'excel',
            'report_type' => 'staff_workload'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment; filename="operational_staff_workload_report_', $contentDisposition);
        $this->assertStringEndsWith('.csv"', $contentDisposition);
    }
}
