<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with(['serviceRequest.client', 'preparedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Quotations/Index', [
            'quotations' => $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString(),
            'filters'    => $request->only(['status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Quotations/Create', [
            'serviceRequests' => ServiceRequest::with('client')
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_request_id' => 'required|exists:service_requests,id',
            'description'        => 'required|string',
            'amount'             => 'required|numeric|min:0',
            'currency'           => 'required|in:USD,ZWL',
            'valid_until'        => 'required|date|after:today',
            'notes'              => 'nullable|string',
        ]);

        $validated['prepared_by'] = Auth::id();
        $validated['status']      = 'draft';

        Quotation::create($validated);

        return redirect()->route('quotations.index')->with('success', 'Quotation created successfully.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['serviceRequest.client', 'preparedBy']);
        return Inertia::render('Quotations/Show', ['quotation' => $quotation]);
    }

    public function edit(Quotation $quotation)
    {
        return Inertia::render('Quotations/Edit', [
            'quotation'       => $quotation,
            'serviceRequests' => ServiceRequest::with('client')->get(),
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'status'      => 'sometimes|in:draft,submitted,approved,rejected',
            'amount'      => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
            'notes'       => 'nullable|string',
        ]);

        $quotation->update($validated);

        return redirect()->back()->with('success', 'Quotation updated.');
    }

    public function destroy(Quotation $quotation)
    {
        $quotation->delete();
        return redirect()->route('quotations.index')->with('success', 'Quotation deleted.');
    }
}
