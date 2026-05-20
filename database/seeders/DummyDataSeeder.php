<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\Quotation;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        $admin = User::first();

        $client = Client::create([
            'client_type' => 'organization',
            'organization' => 'Ministry of Education',
            'contact_person' => 'John Doe',
            'email' => 'contact@moe.gov.zw',
            'phone' => '+263772123456',
            'address' => 'Harare, Zimbabwe',
            'status' => 'active',
        ]);

        $client2 = Client::create([
            'client_type' => 'individual',
            'contact_person' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+263771112222',
            'status' => 'active',
        ]);

        $request1 = ServiceRequest::create([
            'reference_number' => 'SR-20231024-001',
            'client_id' => $client->id,
            'service_category' => 'translation',
            'title' => 'Translate Legal Curriculum to Shona',
            'description' => 'Translate the entire standard legal curriculum document.',
            'source_language' => 'English',
            'target_language' => 'Shona',
            'priority' => 'high',
            'status' => 'in_progress',
            'submitted_by' => $admin ? $admin->id : 1,
            'deadline' => now()->addDays(14),
        ]);

        $request2 = ServiceRequest::create([
            'reference_number' => 'SR-20231025-002',
            'client_id' => $client2->id,
            'service_category' => 'brailling',
            'title' => 'Braille Transcription for Examination Papers',
            'description' => 'Grade 7 final examinations braille copies.',
            'priority' => 'urgent',
            'status' => 'pending',
            'submitted_by' => $admin ? $admin->id : 1,
        ]);

        Quotation::create([
            'reference_number' => 'QT-20231024-001',
            'service_request_id' => $request1->id,
            'description' => 'Standard translation of curriculum',
            'amount' => 1500.00,
            'currency' => 'USD',
            'status' => 'approved',
            'valid_until' => now()->addDays(30),
            'prepared_by' => $admin ? $admin->id : 1,
        ]);

        Quotation::create([
            'reference_number' => 'QT-20231025-002',
            'service_request_id' => $request2->id,
            'description' => 'Braille conversion for exam papers',
            'amount' => 500.00,
            'currency' => 'USD',
            'status' => 'draft',
            'valid_until' => now()->addDays(30),
            'prepared_by' => $admin ? $admin->id : 1,
        ]);

        $assignment = \App\Models\Assignment::create([
            'service_request_id' => $request1->id,
            'assigned_to' => $admin ? $admin->id : 1,
            'assigned_by' => $admin ? $admin->id : 1,
            'role_in_task' => 'translator',
            'status' => 'in_progress',
        ]);

        Task::create([
            'assignment_id' => $assignment->id,
            'title' => 'Translate Chapter 1-5',
            'description' => 'Initial translation phase for the curriculum.',
            'priority' => 'high',
            'status' => 'in_progress',
            'due_date' => now()->addDays(5),
        ]);
    }
}
