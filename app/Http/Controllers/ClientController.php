<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
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
            $query->where('status', $request->status);
        }

        return Inertia::render('Clients/Index', [
            'clients' => $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString(),
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
            'email'          => 'required|email|unique:clients,email',
            'phone'          => 'nullable|string|max:30',
            'address'        => 'nullable|string',
            'status'         => 'required|in:active,inactive',
        ]);

        Client::create($validated);

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
        $validated = $request->validate([
            'client_type'    => 'required|in:individual,organization',
            'organization'   => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email'          => 'required|email|unique:clients,email,'.$client->id,
            'phone'          => 'nullable|string|max:30',
            'address'        => 'nullable|string',
            'status'         => 'required|in:active,inactive',
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client deleted.');
    }
}
