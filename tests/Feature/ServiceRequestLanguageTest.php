<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestLanguageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_create_service_request_with_multiple_target_languages()
    {
        $client = Client::create([
            'client_type'    => 'individual',
            'contact_person' => 'John Doe',
            'email'          => 'johndoe@example.com',
            'phone'          => '123456789',
            'address'        => '123 Client St',
            'status'         => 'active',
        ]);
        $clientUser = User::where('email', 'johndoe@example.com')->first();

        $this->actingAs($clientUser);

        $response = $this->post(route('service-requests.store'), [
            'client_id' => $client->id,
            'service_category' => 'translation',
            'title' => 'Multi Language Document Translation',
            'description' => 'Translate this document into multiple target languages.',
            'source_language' => 'English',
            'target_language' => ['Shona', 'Ndebele', 'Chewa'],
            'priority' => 'high',
            'deadline' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $request = ServiceRequest::first();
        $this->assertEquals(['Shona', 'Ndebele', 'Chewa'], $request->target_language);

        // Test accessor handles legacy CSV strings gracefully
        $request->target_language = 'Shona, Ndebele, Chewa';
        $request->save();

        $requestFresh = ServiceRequest::first();
        $this->assertEquals(['Shona', 'Ndebele', 'Chewa'], $requestFresh->target_language);
    }
}
