<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Auth::user() && Auth::user()->hasRole('student')) {
                abort(403, 'Unauthorized. Students cannot access client modules.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Assignment::with(['serviceRequest.client', 'assignedTo', 'assignedBy']);

        if ($user->hasRole('language_expert') || $user->hasRole('part_time_staff')) {
            $query->where('assigned_to', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client')) {
            $query->whereHas('serviceRequest.client', function ($q) use ($request) {
                $q->where('contact_person', 'like', '%'.$request->client.'%')
                  ->orWhere('organization', 'like', '%'.$request->client.'%');
            });
        }

        return Inertia::render('Assignments/Index', [
            'assignments' => $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString(),
            'filters'     => [
                'status' => $request->status,
                'client' => $request->client,
            ],
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
        $user = Auth::user();
        if (($user->hasRole('language_expert') || $user->hasRole('part_time_staff')) && $assignment->assigned_to !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $assignment->load(['serviceRequest.client', 'serviceRequest.documents.uploader', 'assignedTo', 'assignedBy', 'tasks', 'documents.uploader']);
        return Inertia::render('Assignments/Show', ['assignment' => $assignment]);
    }

    public function completeAssignment(Request $request, Assignment $assignment)
    {
        $user = Auth::user();
        if (($user->hasRole('language_expert') || $user->hasRole('part_time_staff')) && $assignment->assigned_to !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'file'  => 'required|file|max:10240', // max 10MB
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $filePath = $file->store('documents', 'public');
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            $assignment->documents()->create([
                'uploaded_by' => $user->id,
                'filename'    => $filename,
                'file_path'   => $filePath,
                'file_size'   => $fileSize,
                'mime_type'   => $mimeType,
                'description' => $request->input('notes', 'Assignment deliverable submission'),
            ]);
        }

        $assignment->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'notes'        => $request->input('notes', $assignment->notes),
        ]);

        if ($assignment->serviceRequest) {
            $assignment->serviceRequest->update(['status' => 'review']);
        }

        return redirect()->back()->with('success', 'Assignment completed and deliverable submitted successfully.');
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

        $assignment->fill($validated);
        if (!$assignment->isDirty()) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        $assignment->save();

        return redirect()->back()->with('success', 'Assignment updated.');
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return redirect()->route('assignments.index')->with('success', 'Assignment deleted.');
    }
}
