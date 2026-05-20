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

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $quotations = $query->orderBy('created_at', 'desc')->paginate(10);

        if ($request->wantsJson()) {
            return response()->json($quotations);
        }

        return Inertia::render('Quotations/Index', [
            'quotations' => $quotations
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_request_id' => 'required|exists:service_requests,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'description' => 'required|string',
            'valid_until' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $validated['prepared_by'] = Auth::id();
        $validated['status'] = 'draft';

        $quotation = Quotation::create($validated);
        
        $quotation->serviceRequest->update(['status' => 'quoted']);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Quotation created', 'data' => $quotation], 201);
        }

        return back()->with('success', 'Quotation created successfully.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['serviceRequest.client', 'preparedBy', 'approvals.approver']);

        if (request()->wantsJson()) {
            return response()->json($quotation);
        }

        return Inertia::render('Quotations/Show', [
            'quotation' => $quotation
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:draft,pending_approval,approved,rejected,expired',
            'amount' => 'sometimes|numeric|min:0',
        ]);

        $quotation->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Quotation updated', 'data' => $quotation]);
        }

        return back()->with('success', 'Quotation updated.');
    }
}
