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
        $user = Auth::user();
        $query = Quotation::with(['serviceRequest.client', 'preparedBy', 'approvals.approver']);

        // Scope index queries according to user roles
        if ($user->hasRole('client')) {
            $query->whereHas('serviceRequest', function ($q) use ($user) {
                $q->where('submitted_by', $user->id);
            })->where('status', 'approved');
        } elseif ($user->hasRole('secretary')) {
            $query->whereIn('status', ['approved', 'rejected']);
        } elseif ($user->hasRole('admin_assistant')) {
            $query->where(function ($q) use ($user) {
                $q->where('prepared_by', $user->id)
                  ->orWhereIn('status', ['approved', 'rejected']);
            });
        } elseif ($user->hasRole('deputy_director')) {
            $query->whereIn('status', ['submitted', 'pending_approval', 'approved', 'rejected']);
        } elseif ($user->hasRole('executive_director')) {
            $query->whereIn('status', ['pending_approval', 'approved', 'rejected']);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('reference_number', 'like', '%'.$request->search.'%')
                  ->orWhereHas('serviceRequest.client', function ($sq) use ($request) {
                      $sq->where('contact_person', 'like', '%'.$request->search.'%')
                        ->orWhere('organization', 'like', '%'.$request->search.'%');
                  })
                  ->orWhereHas('serviceRequest', function ($sq) use ($request) {
                      $sq->where('reference_number', 'like', '%'.$request->search.'%');
                  });
            });
        }

        return Inertia::render('Quotations/Index', [
            'quotations' => $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString(),
            'filters'    => [
                'status' => $request->status,
                'search' => $request->search,
            ],
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin_assistant') && !$user->hasRole('ict_administrator')) {
            abort(403, 'Only Admin Assistants and ICT Administrators can generate quotations.');
        }

        return Inertia::render('Quotations/Create', [
            'serviceRequests' => ServiceRequest::with('client')
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin_assistant') && !$user->hasRole('ict_administrator')) {
            abort(403, 'Only Admin Assistants and ICT Administrators can generate quotations.');
        }

        $validated = $request->validate([
            'service_request_id' => 'required|exists:service_requests,id',
            'description'        => 'nullable|string',
            'amount'             => 'required|numeric|min:0',
            'currency'           => 'required|in:USD,ZWL,ZAR',
            'valid_until'        => 'required|date|after:today',
            'notes'              => 'nullable|string',
            'status'             => 'sometimes|in:draft,submitted',
            'line_items'         => 'nullable|array',
        ]);

        $validated['prepared_by'] = Auth::id();
        $validated['status']      = $request->input('status', 'submitted');
        $validated['line_items']  = $request->input('line_items', []);

        if (empty($validated['description'])) {
            $validated['description'] = 'Services rendered as per line items.';
        }

        Quotation::create($validated);

        return redirect()->route('quotations.index')->with('success', 'Quotation generated and submitted successfully.');
    }

    public function show(Quotation $quotation)
    {
        $user = Auth::user();
        
        // Scope checks for show
        if ($user->hasRole('client')) {
            if ($quotation->serviceRequest->submitted_by !== $user->id || $quotation->status !== 'approved') {
                abort(403, 'Unauthorized.');
            }
        } elseif ($user->hasRole('secretary')) {
            if (!in_array($quotation->status, ['approved', 'rejected'])) {
                abort(403, 'Unauthorized.');
            }
        } elseif ($user->hasRole('admin_assistant')) {
            if ($quotation->prepared_by !== $user->id && !in_array($quotation->status, ['approved', 'rejected'])) {
                abort(403, 'Unauthorized.');
            }
        } elseif ($user->hasRole('deputy_director')) {
            if (!in_array($quotation->status, ['submitted', 'pending_approval', 'approved', 'rejected'])) {
                abort(403, 'Unauthorized.');
            }
        } elseif ($user->hasRole('executive_director')) {
            if (!in_array($quotation->status, ['pending_approval', 'approved', 'rejected'])) {
                abort(403, 'Unauthorized.');
            }
        }

        $quotation->load(['serviceRequest.client', 'preparedBy', 'approvals.approver']);
        return Inertia::render('Quotations/Show', ['quotation' => $quotation]);
    }

    public function approve(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'status'   => 'required|in:approved,rejected,review',
            'comments' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $isDeputy = $user->hasRole('deputy_director');
        $isExecutive = $user->hasRole('executive_director') || $user->hasRole('ict_administrator');

        if (!$isDeputy && !$isExecutive) {
            abort(403, 'Unauthorized action.');
        }

        // Deputy Director Recommendation (Stage 1)
        if ($isDeputy) {
            if ($quotation->status !== 'submitted') {
                return redirect()->back()->with('error', 'Only submitted quotations can be recommended.');
            }

            if ($validated['status'] === 'approved') {
                $quotation->update(['status' => 'pending_approval']);
                $quotation->approvals()->create([
                    'stage'       => 1,
                    'approver_id' => $user->id,
                    'status'      => 'approved',
                    'comments'    => $validated['comments'],
                    'approved_at' => now(),
                ]);
                $msg = 'Quotation recommended and pushed to the Executive Director.';
            } elseif ($validated['status'] === 'review') {
                $quotation->update(['status' => 'draft']);
                $quotation->approvals()->create([
                    'stage'       => 1,
                    'approver_id' => $user->id,
                    'status'      => 'rejected',
                    'comments'    => '[Revision Requested] ' . $validated['comments'],
                    'approved_at' => now(),
                ]);
                $msg = 'Quotation returned to creator for revision.';
            } else {
                $quotation->update(['status' => 'rejected']);
                $quotation->approvals()->create([
                    'stage'       => 1,
                    'approver_id' => $user->id,
                    'status'      => 'rejected',
                    'comments'    => $validated['comments'],
                    'approved_at' => now(),
                ]);
                $msg = 'Quotation rejected successfully.';
            }
        }

        // Executive Director Final Approval (Stage 2)
        if ($isExecutive) {
            if ($quotation->status !== 'pending_approval' && $quotation->status !== 'submitted') {
                return redirect()->back()->with('error', 'Invalid quotation state for final approval.');
            }

            if ($validated['status'] === 'approved') {
                $quotation->update(['status' => 'approved']);
                $quotation->approvals()->create([
                    'stage'       => 2,
                    'approver_id' => $user->id,
                    'status'      => 'approved',
                    'comments'    => $validated['comments'],
                    'approved_at' => now(),
                ]);

                if ($quotation->serviceRequest) {
                    $quotation->serviceRequest->update(['status' => 'quoted']);
                }

                $msg = 'Quotation approved successfully.';
            } else {
                $quotation->update(['status' => 'rejected']);
                $quotation->approvals()->create([
                    'stage'       => 2,
                    'approver_id' => $user->id,
                    'status'      => 'rejected',
                    'comments'    => $validated['comments'],
                    'approved_at' => now(),
                ]);
                $msg = 'Quotation rejected successfully.';
            }
        }

        return redirect()->route('quotations.index')->with('success', $msg);
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
        $user = Auth::user();
        if ($quotation->prepared_by !== $user->id && !$user->hasRole('admin_assistant') && !$user->hasRole('ict_administrator')) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'service_request_id' => 'sometimes|required|exists:service_requests,id',
            'description'        => 'nullable|string',
            'amount'             => 'required|numeric|min:0',
            'currency'           => 'required|in:USD,ZWL,ZAR',
            'valid_until'        => 'required|date',
            'notes'              => 'nullable|string',
            'status'             => 'sometimes|in:draft,submitted',
            'line_items'         => 'nullable|array',
        ]);

        $validated['line_items'] = $request->input('line_items', []);

        if (empty($validated['description'])) {
            $validated['description'] = 'Services rendered as per line items.';
        }

        $quotation->update($validated);

        return redirect()->route('quotations.show', $quotation->id)->with('success', 'Quotation updated successfully.');
    }

    public function destroy(Quotation $quotation)
    {
        $quotation->delete();
        return redirect()->route('quotations.index')->with('success', 'Quotation deleted.');
    }
}
