<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'approvable_type' => 'required|string',
            'approvable_id' => 'required|integer',
            'stage' => 'required|integer|min:1',
            'status' => 'required|in:approved,rejected,escalated',
            'comments' => 'nullable|string',
        ]);

        $validated['approver_id'] = Auth::id();
        $validated['approved_at'] = now();

        $approval = Approval::create($validated);

        $parent = $approval->approvable;
        if ($parent && method_exists($parent, 'update')) {
            if ($validated['status'] == 'approved') {
                $parent->update(['status' => 'approved']);
            } elseif ($validated['status'] == 'rejected') {
                $parent->update(['status' => 'rejected']);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Approval processed', 'data' => $approval], 201);
        }

        return back()->with('success', 'Approval recorded.');
    }
}
