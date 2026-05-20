<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Assignment::with(['serviceRequest', 'user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if (Auth::user()->hasRole('part_time_staff') || Auth::user()->hasRole('language_expert')) {
            $query->where('user_id', Auth::id());
        }

        $assignments = $query->orderBy('created_at', 'desc')->paginate(10);

        if ($request->wantsJson()) {
            return response()->json($assignments);
        }

        return Inertia::render('Assignments/Index', [
            'assignments' => $assignments
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_request_id' => 'required|exists:service_requests,id',
            'user_id' => 'required|exists:users,id',
            'role_type' => 'required|in:translator,editor,braillist,consultant,sign_language_interpreter',
            'agreed_fee' => 'nullable|numeric|min:0',
        ]);

        $validated['status'] = 'pending';

        $assignment = Assignment::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Assignment created', 'data' => $assignment], 201);
        }

        return back()->with('success', 'Staff assigned to request successfully.');
    }

    public function show(Assignment $assignment)
    {
        $assignment->load(['serviceRequest.client', 'user']);

        if (request()->wantsJson()) {
            return response()->json($assignment);
        }

        return Inertia::render('Assignments/Show', [
            'assignment' => $assignment
        ]);
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,accepted,rejected,in_progress,completed',
            'feedback' => 'nullable|string',
        ]);

        $assignment->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Assignment updated', 'data' => $assignment]);
        }

        return back()->with('success', 'Assignment updated.');
    }
}
