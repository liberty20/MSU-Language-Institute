<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /** @test */
    public function client_can_chat_with_admin()
    {
        $client = User::factory()->create([
            'primary_category' => 'Client',
            'is_active' => true,
        ]);
        $client->assignRole('client');

        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'ict_administrator');
        })->first();

        // Clear existing notifications generated during user creation and seeding
        $admin->notifications()->delete();

        // 1. Send message from Client to Admin (should succeed)
        $response = $this->actingAs($client)->post(route('chat.send'), [
            'receiver_id' => $admin->id,
            'message' => 'Hello Admin, I have a request.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $client->id,
            'receiver_id' => $admin->id,
            'message' => 'Hello Admin, I have a request.',
        ]);

        // 2. Receive notification for Admin
        $this->assertEquals(1, $admin->unreadNotifications()->count());

        // 3. Mark as read
        $responseRead = $this->actingAs($admin)->post(route('chat.read', $client->id));
        $responseRead->assertStatus(200);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $client->id,
            'receiver_id' => $admin->id,
            'read_at' => now()->toDateTimeString(),
        ]);
    }

    /** @test */
    public function peer_to_peer_client_messaging_is_blocked()
    {
        $clientA = User::factory()->create([
            'primary_category' => 'Client',
            'is_active' => true,
        ]);
        $clientA->assignRole('client');

        $clientB = User::factory()->create([
            'primary_category' => 'Client',
            'is_active' => true,
        ]);
        $clientB->assignRole('client');

        // Sending message from Client A to Client B should fail (403 Forbidden)
        $response = $this->actingAs($clientA)->post(route('chat.send'), [
            'receiver_id' => $clientB->id,
            'message' => 'Hello other client!',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('messages', [
            'sender_id' => $clientA->id,
            'receiver_id' => $clientB->id,
        ]);
    }
}
