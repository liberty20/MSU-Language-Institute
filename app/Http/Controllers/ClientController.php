<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Auth::user() && Auth::user()->primary_category === 'Student') {
                abort(403, 'Unauthorized. Students cannot access client modules.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Client::withCount('serviceRequests');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('organization', 'like', '%'.$request->search.'%')
                  ->orWhere('contact_person', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->whereStatus($request->status);
        }

        return Inertia::render('Clients/Index', [
            'clients' => $query->orderByDesc('created_at')->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Clients/Create');
    }

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

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load(['serviceRequests' => fn($q) => $q->orderBy('created_at', 'desc')->take(10)]);
        return Inertia::render('Clients/Show', ['client' => $client]);
    }

    public function edit(Client $client)
    {
        return Inertia::render('Clients/Edit', ['client' => $client]);
    }

    public function update(Request $request, Client $client)
    {
        $user = User::whereEmail($client->email)->first();

        $rules = [
            'client_type'    => 'required|in:individual,organization',
            'organization'   => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email'          => 'required|email|unique:clients,email,'.$client->id,
            'phone'          => 'nullable|string|max:30',
            'address'        => 'nullable|string',
            'status'         => 'required|in:active,inactive',
        ];

        if ($user) {
            $rules['email'] .= '|unique:users,email,'.$user->id;
        } else {
            $rules['email'] .= '|unique:users,email';
        }

        $validated = $request->validate($rules);

        $oldEmail = $client->email;
        
        $client->fill($validated);
        $user = User::whereEmail($oldEmail)->first();
        $userDirty = false;
        if ($user) {
            $user->fill([
                'name'      => $validated['organization'] ?: $validated['contact_person'],
                'email'     => $validated['email'],
                'phone'     => $validated['phone'] ?? null,
                'is_active' => $validated['status'] === 'active',
            ]);
            $userDirty = $user->isDirty();
        }

        if (!$client->isDirty() && !$userDirty) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        \App\Services\UserBackupService::$isSyncing = true;
        try {
            $client->save();
            if ($user) {
                $user->save();
            }
        } finally {
            \App\Services\UserBackupService::$isSyncing = false;
        }

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $user = User::whereEmail($client->email)->first();
        if ($user) {
            $user->delete();
        }
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client deleted.');
    }
}
