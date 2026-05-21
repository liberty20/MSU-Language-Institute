<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Assignment::with(['serviceRequest.client', 'assignedTo', 'assignedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Assignments/Index', [
            'assignments' => $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString(),
            'filters'     => $request->only(['status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Assignments/Create', [
            'serviceRequests' => ServiceRequest::with('client')
                ->whereNotIn('status', ['completed', 'cancelled'])->get(),
            'staff' => User::whereHas('roles', fn($q) =>
                $q->whereIn('name', ['language_expert', 'part_time_staff', 'secretary'])
            )->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_request_id' => 'required|exists:service_requests,id',
            'assigned_to'        => 'required|exists:users,id',
            'role_in_task'       => 'nullable|string|max:100',
            'notes'              => 'nullable|string',
        ]);

        $validated['assigned_by'] = Auth::id();
        $validated['status']      = 'assigned';

        Assignment::create($validated);

        return redirect()->route('assignments.index')->with('success', 'Assignment created successfully.');
    }

    public function show(Assignment $assignment)
    {
        $assignment->load(['serviceRequest.client', 'assignedTo', 'assignedBy', 'tasks']);
        return Inertia::render('Assignments/Show', ['assignment' => $assignment]);
    }

    public function edit(Assignment $assignment)
    {
        return Inertia::render('Assignments/Edit', [
            'assignment'      => $assignment,
            'serviceRequests' => ServiceRequest::with('client')->get(),
            'staff'           => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'status'       => 'sometimes|in:assigned,accepted,in_progress,completed,reassigned',
            'role_in_task' => 'sometimes|nullable|string|max:100',
            'notes'        => 'nullable|string',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'in_progress' && !$assignment->started_at) {
            $validated['started_at'] = now();
        }
        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $assignment->update($validated);

        return redirect()->back()->with('success', 'Assignment updated.');
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return redirect()->route('assignments.index')->with('success', 'Assignment deleted.');
    }
}
