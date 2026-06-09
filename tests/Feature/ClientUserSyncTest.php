<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientUserSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function creating_a_client_automatically_creates_the_corresponding_user_with_client_role()
    {
        $client = Client::create([
            'client_type'    => 'individual',
            'contact_person' => 'John Doe',
            'email'          => 'johndoe@example.com',
            'phone'          => '123456789',
            'address'        => '123 Client St',
            'status'         => 'active',
        ]);

        $user = User::where('email', 'johndoe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('123456789', $user->phone);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('client'));
        $this->assertEquals($user->id, $client->fresh()->user_id);
    }

    /** @test */
    public function updating_a_client_automatically_updates_the_corresponding_user()
    {
        $client = Client::create([
            'client_type'    => 'individual',
            'contact_person' => 'Jane Smith',
            'email'          => 'janesmith@example.com',
            'phone'          => '987654321',
            'address'        => '456 Client St',
            'status'         => 'active',
        ]);

        $client->update([
            'contact_person' => 'Jane Doe',
            'email'          => 'janedoe@example.com',
            'phone'          => '111222333',
            'status'         => 'inactive',
        ]);

        $user = User::where('email', 'janedoe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('111222333', $user->phone);
        $this->assertFalse($user->is_active);

        // The old user with previous email should either be updated or not exist under old email
        $this->assertNull(User::where('email', 'janesmith@example.com')->first());
    }

    /** @test */
    public function deleting_a_client_automatically_deletes_the_corresponding_user()
    {
        $client = Client::create([
            'client_type'    => 'individual',
            'contact_person' => 'Bob Marley',
            'email'          => 'bob@example.com',
            'phone'          => '123456',
            'address'        => 'Reggae Ave',
            'status'         => 'active',
        ]);

        $user = User::where('email', 'bob@example.com')->first();
        $this->assertNotNull($user);

        $client->delete();

        $this->assertNull(User::where('email', 'bob@example.com')->first());
    }

    /** @test */
    public function creating_a_user_with_client_role_automatically_creates_the_corresponding_client()
    {
        $user = User::create([
            'name'      => 'Alice Wonderland',
            'email'     => 'alice@example.com',
            'phone'     => '555666777',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole('client');

        $client = Client::where('user_id', $user->id)->first();
        $this->assertNotNull($client);
        $this->assertEquals('Alice Wonderland', $client->contact_person);
        $this->assertEquals('alice@example.com', $client->email);
        $this->assertEquals('555666777', $client->phone);
        $this->assertEquals('active', $client->status);
        $this->assertEquals('individual', $client->client_type);
    }

    /** @test */
    public function updating_a_user_with_client_role_automatically_updates_the_corresponding_client()
    {
        $user = User::create([
            'name'      => 'Charlie Brown',
            'email'     => 'charlie@example.com',
            'phone'     => '444555666',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole('client');

        $user->update([
            'name'      => 'Charlie Snoopy',
            'email'     => 'snoopy@example.com',
            'phone'     => '777888999',
            'is_active' => false,
        ]);

        $client = Client::where('user_id', $user->id)->first();
        $this->assertNotNull($client);
        $this->assertEquals('Charlie Snoopy', $client->contact_person);
        $this->assertEquals('snoopy@example.com', $client->email);
        $this->assertEquals('777888999', $client->phone);
        $this->assertEquals('inactive', $client->status);
    }

    /** @test */
    public function deleting_a_user_with_client_role_automatically_deletes_the_corresponding_client()
    {
        $user = User::create([
            'name'      => 'Dave Clark',
            'email'     => 'dave@example.com',
            'phone'     => '999888777',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole('client');

        $this->assertNotNull(Client::where('user_id', $user->id)->first());

        $user->delete();

        $this->assertNull(Client::where('user_id', $user->id)->first());
    }

    /** @test */
    public function removing_client_role_from_user_automatically_deletes_the_corresponding_client()
    {
        $user = User::create([
            'name'      => 'Eve Adams',
            'email'     => 'eve@example.com',
            'phone'     => '333444555',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole('client');

        $this->assertNotNull(Client::where('user_id', $user->id)->first());

        // Sync with a different role, removing 'client'
        $user->syncRoles(['receptionist']);

        $this->assertNull(Client::where('user_id', $user->id)->first());
    }
}
