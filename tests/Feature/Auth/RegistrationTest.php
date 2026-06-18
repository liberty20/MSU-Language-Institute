<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $this->seed();
        $this->withoutExceptionHandling();

        $response = $this->post('/register', [
            'client_type' => 'organization',
            'organization' => 'MSU Language Institute Client',
            'contact_person' => 'John Doe',
            'email' => 'new_client@example.com',
            'phone' => '+263 77 123 4567',
            'status' => 'active',
            'address' => 'Senga Road, Gweru',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);

        // Verify that the User record was created
        $user = User::whereEmail('new_client@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('MSU Language Institute Client', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('client'));
        $this->assertEquals('Client', $user->primary_category);

        // Verify that the Client record was created and linked
        $client = Client::whereEmail('new_client@example.com')->first();
        $this->assertNotNull($client);
        $this->assertEquals('organization', $client->client_type);
        $this->assertEquals('MSU Language Institute Client', $client->organization);
        $this->assertEquals('John Doe', $client->contact_person);
        $this->assertEquals('+263 77 123 4567', $client->phone);
        $this->assertEquals('active', $client->status);
        $this->assertEquals('Senga Road, Gweru', $client->address);
        $this->assertEquals($user->id, $client->user_id);

        // Log out the user and attempt to log in using the newly registered credentials
        Auth::logout();
        $this->assertGuest();

        $loginResponse = $this->post('/login', [
            'email' => 'new_client@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $loginResponse->assertRedirect(RouteServiceProvider::HOME);
    }
}
