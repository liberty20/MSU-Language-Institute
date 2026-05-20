<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::orderBy('organization')->orderBy('contact_person')->paginate(10);

        if ($request->wantsJson()) {
            return response()->json($clients);
        }

        return Inertia::render('Clients/Index', [
            'clients' => $clients
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'client_type' => 'required|in:individual,organization,government',
        ]);

        $client = Client::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Client created', 'data' => $client], 201);
        }

        return back()->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load('serviceRequests');

        if (request()->wantsJson()) {
            return response()->json($client);
        }

        return Inertia::render('Clients/Show', [
            'client' => $client
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'organization' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'client_type' => 'required|in:individual,organization,government',
            'status' => 'required|in:active,inactive',
        ]);

        $client->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Client updated', 'data' => $client]);
        }

        return back()->with('success', 'Client updated.');
    }
}
