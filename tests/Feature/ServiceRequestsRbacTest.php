<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestsRbacTest extends TestCase
{
    use RefreshDatabase;

    protected $adminAssistant;
    protected $secretary;
    protected $coordinator;
    protected $client;
    protected $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // 1. Create Roles & Users
        $this->adminAssistant = User::factory()->create(['name' => 'Admin Assistant', 'primary_category' => 'Staff']);
        $this->adminAssistant->assignRole('admin_assistant');

        $this->secretary = User::factory()->create(['name' => 'Secretary', 'primary_category' => 'Staff']);
        $this->secretary->assignRole('secretary');

        $this->coordinator = User::factory()->create(['name' => 'Coordinator', 'primary_category' => 'Staff']);
        $this->coordinator->assignRole('coordinator');

        $this->client = Client::create([
            'client_type'    => 'individual',
            'contact_person' => 'John Doe',
            'email'          => 'johndoe@example.com',
            'phone'          => '123456789',
            'address'        => '123 Client St',
            'status'         => 'active',
        ]);
        $this->clientUser = User::where('email', 'johndoe@example.com')->first();
    }

    public function test_admin_assistant_can_access_service_requests_index_and_create()
    {
        $response = $this->actingAs($this->adminAssistant)->get(route('service-requests.index'));
        $response->assertStatus(200);

        $responseCreate = $this->actingAs($this->adminAssistant)->get(route('service-requests.create'));
        $responseCreate->assertStatus(200);

        $responseStore = $this->actingAs($this->adminAssistant)->post(route('service-requests.store'), [
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Admin Asst Test Request',
            'description' => 'Test description',
            'priority' => 'medium',
        ]);
        $responseStore->assertRedirect(route('service-requests.index'));
        $this->assertDatabaseHas('service_requests', ['title' => 'Admin Asst Test Request']);
    }

    public function test_secretary_can_access_service_requests_index_and_create()
    {
        $response = $this->actingAs($this->secretary)->get(route('service-requests.index'));
        $response->assertStatus(200);

        $responseCreate = $this->actingAs($this->secretary)->get(route('service-requests.create'));
        $responseCreate->assertStatus(200);

        $responseStore = $this->actingAs($this->secretary)->post(route('service-requests.store'), [
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Secretary Test Request',
            'description' => 'Test description',
            'priority' => 'medium',
        ]);
        $responseStore->assertRedirect(route('service-requests.index'));
        $this->assertDatabaseHas('service_requests', ['title' => 'Secretary Test Request']);
    }

    public function test_coordinator_can_access_index_but_cannot_create_or_store()
    {
        // 1. Can view index
        $responseIndex = $this->actingAs($this->coordinator)->get(route('service-requests.index'));
        $responseIndex->assertStatus(200);

        // 2. Cannot view create form
        $responseCreate = $this->actingAs($this->coordinator)->get(route('service-requests.create'));
        $responseCreate->assertStatus(403);

        // 3. Cannot submit store post request
        $responseStore = $this->actingAs($this->coordinator)->post(route('service-requests.store'), [
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Coordinator Test Request',
            'description' => 'Test description',
            'priority' => 'medium',
        ]);
        $responseStore->assertStatus(403);
        $this->assertDatabaseMissing('service_requests', ['title' => 'Coordinator Test Request']);
    }
}
