<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\ServiceRequest;
use App\Models\Quotation;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\ProcurementRequest;
use App\Models\User;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        $clients = [
            ['client_type' => 'organization', 'organization' => 'Ministry of Health', 'contact_person' => 'Dr. Moyo', 'email' => 'moyo@health.gov.zw', 'phone' => '0771234567', 'status' => 'active'],
            ['client_type' => 'organization', 'organization' => 'UNICEF Zimbabwe', 'contact_person' => 'Jane Smith', 'email' => 'jsmith@unicef.org', 'phone' => '0772345678', 'status' => 'active'],
            ['client_type' => 'individual', 'organization' => null, 'contact_person' => 'Tinashe Ndlovu', 'email' => 'tinashe@gmail.com', 'phone' => '0773456789', 'status' => 'active'],
            ['client_type' => 'organization', 'organization' => 'NSSA', 'contact_person' => 'Peter Chigumba', 'email' => 'peter@nssa.org.zw', 'phone' => '0774567890', 'status' => 'active'],
            ['client_type' => 'individual', 'organization' => null, 'contact_person' => 'Grace Chikwanha', 'email' => 'grace@yahoo.com', 'phone' => '0775678901', 'status' => 'active'],
        ];

        foreach ($clients as $clientData) {
            Client::create($clientData);
        }

        $clientUser = User::where('email', 'client@example.com')->first();
        $adminUser  = User::where('email', 'admin@msunli.edu')->first();
        $expertUser = User::where('email', 'expert@msunli.edu')->first();
        
        $requests = [
            ['client_id' => 1, 'service_category' => 'translation', 'title' => 'COVID-19 Guidelines Translation to Shona', 'description' => 'Translate the national guidelines to Shona.', 'source_language' => 'English', 'target_language' => 'Shona', 'priority' => 'urgent', 'status' => 'in_progress', 'deadline' => Carbon::now()->addDays(5)],
            ['client_id' => 2, 'service_category' => 'brailling', 'title' => 'Child Rights Handbook Brailling', 'description' => 'Brailling of the child rights handbook for visually impaired students.', 'source_language' => 'English', 'target_language' => 'Braille', 'priority' => 'high', 'status' => 'approved', 'deadline' => Carbon::now()->addDays(14)],
            ['client_id' => 3, 'service_category' => 'editing', 'title' => 'PhD Thesis Proofreading', 'description' => 'Editing and proofreading thesis.', 'source_language' => 'English', 'target_language' => 'English', 'priority' => 'medium', 'status' => 'pending', 'deadline' => Carbon::now()->addDays(30)],
            ['client_id' => 4, 'service_category' => 'sign_language', 'title' => 'NSSA Annual Conference Interpretation', 'description' => 'Sign language interpretation for the conference.', 'source_language' => 'English', 'target_language' => 'Sign Language', 'priority' => 'high', 'status' => 'completed', 'deadline' => Carbon::now()->subDays(2)],
        ];

        foreach ($requests as $requestData) {
            $requestData['submitted_by'] = $clientUser->id;
            ServiceRequest::create($requestData);
        }

        Quotation::create(['service_request_id' => 1, 'prepared_by' => $adminUser->id, 'description' => 'Translation of 50 pages', 'amount' => 500.00, 'currency' => 'USD', 'valid_until' => Carbon::now()->addDays(30), 'status' => 'approved']);
        Quotation::create(['service_request_id' => 2, 'prepared_by' => $adminUser->id, 'description' => 'Brailling of 100 pages', 'amount' => 1200.00, 'currency' => 'USD', 'valid_until' => Carbon::now()->addDays(30), 'status' => 'submitted']);
        
        Assignment::create(['service_request_id' => 1, 'assigned_to' => $expertUser->id, 'assigned_by' => $adminUser->id, 'role_in_task' => 'Lead Translator', 'status' => 'in_progress', 'started_at' => Carbon::now()->subDays(2)]);
        Assignment::create(['service_request_id' => 4, 'assigned_to' => $expertUser->id, 'assigned_by' => $adminUser->id, 'role_in_task' => 'Interpreter', 'status' => 'completed', 'started_at' => Carbon::now()->subDays(3), 'completed_at' => Carbon::now()->subDays(2)]);

        Task::create(['assignment_id' => 1, 'title' => 'Translate Chapters 1-5', 'description' => 'First batch of translation.', 'status' => 'completed', 'priority' => 'high', 'due_date' => Carbon::now()->subDay(), 'completed_at' => Carbon::now()->subDay()]);
        Task::create(['assignment_id' => 1, 'title' => 'Translate Chapters 6-10', 'description' => 'Second batch of translation.', 'status' => 'in_progress', 'priority' => 'high', 'due_date' => Carbon::now()->addDays(2)]);

        ProcurementRequest::create(['reference_number' => 'PR-20260520-001', 'title' => 'Braille Paper', 'description' => 'Box of 500 sheets', 'estimated_cost' => 150.00, 'justification' => 'Low stock in brailling unit', 'priority' => 'high', 'status' => 'approved', 'requested_by' => $adminUser->id]);
        ProcurementRequest::create(['reference_number' => 'PR-20260520-002', 'title' => 'New Laptops', 'description' => 'For Editorial Team', 'estimated_cost' => 2400.00, 'justification' => 'Old machines failing', 'priority' => 'medium', 'status' => 'pending_approval', 'requested_by' => $adminUser->id]);
    }
}
