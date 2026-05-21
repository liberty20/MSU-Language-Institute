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
        $query = ServiceRequest::with(['client', 'submittedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                  ->orWhere('reference_number', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->filled('category')) {
            $query->where('service_category', $request->category);
        }

        return Inertia::render('ServiceRequests/Index', [
            'serviceRequests' => $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString(),
            'filters'         => $request->only(['status', 'search', 'category']),
        ]);
    }

    public function create()
    {
        return Inertia::render('ServiceRequests/Create', [
            'clients' => Client::where('status', 'active')->orderBy('contact_person')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'        => 'required|exists:clients,id',
            'service_category' => 'required|in:translation,editing,brailling,sign_language,consultancy,short_courses',
            'title'            => 'required|string|max:255',
            'description'      => 'required|string',
            'source_language'  => 'nullable|string|max:100',
            'target_language'  => 'nullable|string|max:100',
            'priority'         => 'required|in:low,medium,high,urgent',
            'deadline'         => 'nullable|date|after:today',
            'notes'            => 'nullable|string',
        ]);

        $validated['submitted_by'] = Auth::id();
        $validated['status']       = 'pending';

        ServiceRequest::create($validated);

        return redirect()->route('service-requests.index')->with('success', 'Service request submitted successfully.');
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['client', 'submittedBy', 'assignedTo', 'quotations.preparedBy', 'assignments.assignedTo']);
        return Inertia::render('ServiceRequests/Show', ['serviceRequest' => $serviceRequest]);
    }

    public function edit(ServiceRequest $serviceRequest)
    {
        return Inertia::render('ServiceRequests/Edit', [
            'serviceRequest' => $serviceRequest,
            'clients'        => Client::where('status', 'active')->orderBy('contact_person')->get(),
        ]);
    }

    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'status'      => 'sometimes|in:pending,quoted,approved,in_progress,review,completed,cancelled',
            'priority'    => 'sometimes|in:low,medium,high,urgent',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'notes'       => 'nullable|string',
        ]);

        $serviceRequest->update($validated);

        return redirect()->back()->with('success', 'Service request updated.');
    }

    public function destroy(ServiceRequest $serviceRequest)
    {
        $serviceRequest->delete();
        return redirect()->route('service-requests.index')->with('success', 'Service request deleted.');
    }
}
