<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceRequest::with(['client', 'assignedTo', 'submittedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $serviceRequests = $query->orderBy('created_at', 'desc')->paginate(10);

        if ($request->wantsJson()) {
            return response()->json($serviceRequests);
        }

        return Inertia::render('ServiceRequests/Index', [
            'serviceRequests' => $serviceRequests,
            'filters' => $request->only(['status'])
        ]);
    }

    public function create()
    {
        $clients = Client::all();
        
        return Inertia::render('ServiceRequests/Create', [
            'clients' => $clients
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_category' => 'required|in:translation,editing,brailling,consultancy,sign_language',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'source_language' => 'nullable|string|max:100',
            'target_language' => 'nullable|string|max:100',
            'priority' => 'required|in:low,medium,high,urgent',
            'deadline' => 'nullable|date',
        ]);

        $validated['submitted_by'] = Auth::id();
        $validated['status'] = 'pending';

        $serviceRequest = ServiceRequest::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Service request created successfully', 'data' => $serviceRequest], 201);
        }

        return redirect()->route('service-requests.index')->with('success', 'Service Request created successfully.');
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['client', 'assignedTo', 'submittedBy', 'quotations', 'assignments']);

        if (request()->wantsJson()) {
            return response()->json($serviceRequest);
        }

        return Inertia::render('ServiceRequests/Show', [
            'serviceRequest' => $serviceRequest
        ]);
    }

    public function edit(ServiceRequest $serviceRequest)
    {
        // Not used via inertia
    }

    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,quoted,approved,in_progress,review,completed,cancelled',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
        ]);

        $serviceRequest->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Service request updated successfully', 'data' => $serviceRequest]);
        }

        return back()->with('success', 'Service Request updated successfully.');
    }

    public function destroy(ServiceRequest $serviceRequest)
    {
        $serviceRequest->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Service request deleted']);
        }

        return redirect()->route('service-requests.index')->with('success', 'Service request deleted.');
    }
}
