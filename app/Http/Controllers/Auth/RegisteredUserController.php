<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_type'    => 'required|in:individual,organization',
            'organization'   => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email'          => 'required|email|unique:clients,email|unique:users,email',
            'phone'          => 'nullable|string|max:30',
            'address'        => 'nullable|string',
            'status'         => 'required|in:active,inactive',
            'password'       => 'required|string|min:8|confirmed',
        ]);

        $client = null;
        \App\Services\UserBackupService::$isSyncing = true;
        try {
            $client = Client::create($request->except(['password', 'password_confirmation']));

            $user = User::create([
                'name'      => $validated['organization'] ?: $validated['contact_person'],
                'email'     => $validated['email'],
                'password'  => Hash::make($validated['password']),
                'phone'     => $validated['phone'] ?? null,
                'is_active' => $validated['status'] === 'active',
            ]);

            $user->assignRole('client');

            $client->update(['user_id' => $user->id]);
        } finally {
            \App\Services\UserBackupService::$isSyncing = false;
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
