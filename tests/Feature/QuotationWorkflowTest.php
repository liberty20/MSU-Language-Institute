<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\ServiceRequest;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuotationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $assistant;
    protected $secretary;
    protected $coordinator;
    protected $deputy;
    protected $executive;
    protected $serviceRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('ict_administrator');

        $this->assistant = User::factory()->create();
        $this->assistant->assignRole('admin_assistant');

        $this->secretary = User::factory()->create();
        $this->secretary->assignRole('secretary');

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('coordinator');

        $this->deputy = User::factory()->create();
        $this->deputy->assignRole('deputy_director');

        $this->executive = User::factory()->create();
        $this->executive->assignRole('executive_director');

        // Create service request
        $client = Client::create([
            'client_type'    => 'individual',
            'contact_person' => 'Client User',
            'email'          => 'client@example.com',
            'phone'          => '123456',
            'address'        => 'Address',
            'status'         => 'active',
        ]);
        $clientUser = User::where('email', 'client@example.com')->first();

        $this->serviceRequest = ServiceRequest::create([
            'client_id' => $client->id,
            'service_category' => 'translation',
            'title' => 'Translation job',
            'description' => 'Translate description',
            'submitted_by' => $clientUser->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function assistant_secretary_and_admin_can_create_quotations()
    {
        Notification::fake();

        foreach ([$this->assistant, $this->secretary, $this->admin] as $user) {
            $response = $this->actingAs($user)->post(route('quotations.store'), [
                'service_request_id' => $this->serviceRequest->id,
                'description' => 'Test quotation description',
                'amount' => 500,
                'currency' => 'USD',
                'valid_until' => now()->addDays(10)->format('Y-m-d'),
                'status' => 'submitted',
            ]);

            $response->assertStatus(302);
            $response->assertSessionHasNoErrors();
        }

        $this->assertEquals(3, Quotation::count());

        // Notifications should be sent to Coordinator for submitted status
        Notification::assertSentTo($this->coordinator, \App\Notifications\SystemNotification::class);
    }

    /** @test */
    public function other_roles_cannot_create_quotations()
    {
        $response = $this->actingAs($this->deputy)->post(route('quotations.store'), [
            'service_request_id' => $this->serviceRequest->id,
            'description' => 'Unauthorized Quotation',
            'amount' => 500,
            'currency' => 'USD',
            'valid_until' => now()->addDays(10)->format('Y-m-d'),
            'status' => 'submitted',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function full_approval_workflow_stages()
    {
        Notification::fake();

        // 1. Create a quotation as draft
        $quotation = Quotation::create([
            'reference_number' => 'QT-WORKFLOW-01',
            'service_request_id' => $this->serviceRequest->id,
            'prepared_by' => $this->assistant->id,
            'description' => 'Quotation description',
            'amount' => 1500,
            'currency' => 'USD',
            'valid_until' => now()->addDays(10),
            'status' => 'draft',
        ]);

        // 2. Submit to Coordinator
        $response = $this->actingAs($this->assistant)->put(route('quotations.update', $quotation->id), [
            'service_request_id' => $this->serviceRequest->id,
            'description' => 'Quotation description edited',
            'amount' => 1500,
            'currency' => 'USD',
            'valid_until' => now()->addDays(10)->format('Y-m-d'),
            'status' => 'submitted',
        ]);

        $response->assertStatus(302);
        $quotation->refresh();
        $this->assertEquals('submitted', $quotation->status);
        Notification::assertSentTo($this->coordinator, \App\Notifications\SystemNotification::class);

        // 3. Coordinator reviews and forwards to Deputy
        $response = $this->actingAs($this->coordinator)->post(route('quotations.approve', $quotation->id), [
            'status' => 'approved',
            'comments' => 'Coordinator approved and reviewed',
        ]);

        $response->assertStatus(302);
        $quotation->refresh();
        $this->assertEquals('reviewed', $quotation->status);
        Notification::assertSentTo($this->deputy, \App\Notifications\SystemNotification::class);

        // 4. Deputy recommends to Executive
        $response = $this->actingAs($this->deputy)->post(route('quotations.approve', $quotation->id), [
            'status' => 'approved',
            'comments' => 'Deputy recommended',
        ]);

        $response->assertStatus(302);
        $quotation->refresh();
        $this->assertEquals('pending_approval', $quotation->status);
        Notification::assertSentTo($this->executive, \App\Notifications\SystemNotification::class);

        // 5. Executive approves
        $response = $this->actingAs($this->executive)->post(route('quotations.approve', $quotation->id), [
            'status' => 'approved',
            'comments' => 'Executive final approval',
        ]);

        $response->assertStatus(302);
        $quotation->refresh();
        $this->assertEquals('approved', $quotation->status);

        // Service Request should be marked as quoted
        $this->serviceRequest->refresh();
        $this->assertEquals('quoted', $this->serviceRequest->status);
    }

    /** @test */
    public function editing_restrictions_enforced()
    {
        $quotation = Quotation::create([
            'service_request_id' => $this->serviceRequest->id,
            'prepared_by' => $this->assistant->id,
            'description' => 'Quotation description',
            'amount' => 1500,
            'currency' => 'USD',
            'valid_until' => now()->addDays(10),
            'status' => 'submitted',
        ]);

        // Trying to edit submitted quotation should fail
        $response = $this->actingAs($this->assistant)->put(route('quotations.update', $quotation->id), [
            'service_request_id' => $this->serviceRequest->id,
            'amount' => 2000,
            'currency' => 'USD',
            'valid_until' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response->assertSessionHas('error');
        $quotation->refresh();
        $this->assertEquals(1500, (int)$quotation->amount);

        // Formally return quotation for correction (Coordinator -> Creator)
        $this->actingAs($this->coordinator)->post(route('quotations.approve', $quotation->id), [
            'status' => 'review',
            'comments' => 'Return for corrections',
        ]);

        $quotation->refresh();
        $this->assertEquals('draft', $quotation->status);

        // Now editing is permitted
        $response = $this->actingAs($this->assistant)->put(route('quotations.update', $quotation->id), [
            'service_request_id' => $this->serviceRequest->id,
            'description' => 'Corrected Description',
            'amount' => 1800,
            'currency' => 'USD',
            'valid_until' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response->assertStatus(302);
        $quotation->refresh();
        $this->assertEquals(1800, (int)$quotation->amount);
    }

    /** @test */
    public function deputy_and_executive_directors_can_view_quotations_awaiting_review()
    {
        $quotation = Quotation::create([
            'service_request_id' => $this->serviceRequest->id,
            'prepared_by' => $this->assistant->id,
            'description' => 'Awaiting review quotation description',
            'amount' => 1500,
            'currency' => 'USD',
            'valid_until' => now()->addDays(10),
            'status' => 'submitted',
        ]);

        // Deputy Director can view it
        $response = $this->actingAs($this->deputy)->get(route('quotations.show', $quotation->id));
        $response->assertStatus(200);

        // Executive Director can view it
        $response2 = $this->actingAs($this->executive)->get(route('quotations.show', $quotation->id));
        $response2->assertStatus(200);
    }

    /** @test */
    public function coordinator_and_directors_can_view_draft_returned_quotations()
    {
        $quotation = Quotation::create([
            'service_request_id' => $this->serviceRequest->id,
            'prepared_by' => $this->assistant->id,
            'description' => 'Draft/returned quotation description',
            'amount' => 1500,
            'currency' => 'USD',
            'valid_until' => now()->addDays(10),
            'status' => 'draft', // Draft status (either initial or returned)
        ]);

        // Coordinator can view it
        $response = $this->actingAs($this->coordinator)->get(route('quotations.show', $quotation->id));
        $response->assertStatus(200);

        // Deputy Director can view it
        $response2 = $this->actingAs($this->deputy)->get(route('quotations.show', $quotation->id));
        $response2->assertStatus(200);

        // Executive Director can view it
        $response3 = $this->actingAs($this->executive)->get(route('quotations.show', $quotation->id));
        $response3->assertStatus(200);
    }

    /** @test */
    public function secretary_and_assistant_returned_revision_access()
    {
        // 1. Create a quotation prepared by Secretary A
        $quotation = Quotation::create([
            'service_request_id' => $this->serviceRequest->id,
            'prepared_by' => $this->secretary->id,
            'description' => 'Quotation to revise',
            'amount' => 1200,
            'currency' => 'USD',
            'valid_until' => now()->addDays(10),
            'status' => 'draft',
        ]);

        // Secretary A (owner) can view and edit
        $response = $this->actingAs($this->secretary)->get(route('quotations.show', $quotation->id));
        $response->assertStatus(200);
        $response = $this->actingAs($this->secretary)->get(route('quotations.edit', $quotation->id));
        $response->assertStatus(200);

        // Assistant can also view and edit (relaxed staff restriction)
        $response = $this->actingAs($this->assistant)->get(route('quotations.show', $quotation->id));
        $response->assertStatus(200);
        $response = $this->actingAs($this->assistant)->get(route('quotations.edit', $quotation->id));
        $response->assertStatus(200);
    }
}
