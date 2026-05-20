<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['assignedToUser', 'serviceRequest']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Language experts should only see their own tasks
        if (Auth::user()->hasRole('language_expert') || Auth::user()->hasRole('part_time_staff')) {
            $query->where('assigned_to', Auth::id());
        }

        $tasks = $query->orderBy('deadline', 'asc')->paginate(10);

        if ($request->wantsJson()) {
            return response()->json($tasks);
        }

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_request_id' => 'required|exists:service_requests,id',
            'assigned_to' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,review,completed,overdue',
            'deadline' => 'required|date',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $task = Task::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Task assigned successfully', 'data' => $task], 201);
        }

        return back()->with('success', 'Task assigned successfully.');
    }

    public function show(Task $task)
    {
        $task->load(['assignedToUser', 'serviceRequest.client']);

        if (request()->wantsJson()) {
            return response()->json($task);
        }

        return Inertia::render('Tasks/Show', [
            'task' => $task
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,in_progress,review,completed,overdue',
            'actual_hours' => 'sometimes|numeric|min:0',
        ]);

        $task->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Task updated', 'data' => $task]);
        }

        return back()->with('success', 'Task updated successfully.');
    }
}
