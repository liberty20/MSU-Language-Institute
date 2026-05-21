<?php

namespace App\Http\Controllers;

use App\Models\ProcurementRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ProcurementRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ProcurementRequest::with(['requestedBy', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('ProcurementRequests/Index', [
            'procurements' => $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString(),
            'filters'      => $request->only(['status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('ProcurementRequests/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_description' => 'required|string|max:500',
            'quantity'         => 'required|integer|min:1',
            'estimated_cost'   => 'required|numeric|min:0',
            'justification'    => 'required|string',
            'urgency'          => 'required|in:low,medium,high,critical',
        ]);

        $validated['requested_by'] = Auth::id();
        $validated['status']       = 'pending';

        ProcurementRequest::create($validated);

        return redirect()->route('procurement-requests.index')->with('success', 'Procurement request submitted.');
    }

    public function show(ProcurementRequest $procurementRequest)
    {
        $procurementRequest->load(['requestedBy', 'approvedBy']);
        return Inertia::render('ProcurementRequests/Show', ['procurement' => $procurementRequest]);
    }

    public function edit(ProcurementRequest $procurementRequest)
    {
        return Inertia::render('ProcurementRequests/Edit', ['procurement' => $procurementRequest]);
    }

    public function update(Request $request, ProcurementRequest $procurementRequest)
    {
        $validated = $request->validate([
            'status'           => 'sometimes|in:pending,approved,rejected,purchased',
            'rejection_reason' => 'nullable|string',
        ]);

        if (isset($validated['status']) && in_array($validated['status'], ['approved', 'rejected'])) {
            $validated['approved_by'] = Auth::id();
            $validated['approved_at'] = now();
        }

        $procurementRequest->update($validated);

        return redirect()->back()->with('success', 'Procurement request updated.');
    }

    public function destroy(ProcurementRequest $procurementRequest)
    {
        $procurementRequest->delete();
        return redirect()->route('procurement-requests.index')->with('success', 'Request deleted.');
    }
}
