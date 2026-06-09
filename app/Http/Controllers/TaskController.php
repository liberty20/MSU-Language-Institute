<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['assignment.serviceRequest', 'assignment.assignedTo']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        return Inertia::render('Tasks/Index', [
            'tasks'   => $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString(),
            'filters' => $request->only(['status', 'priority']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tasks/Create', [
            'assignments' => Assignment::with(['serviceRequest.client', 'assignedTo'])
                ->whereIn('status', ['assigned', 'accepted', 'in_progress'])->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'priority'      => 'required|in:low,medium,high',
            'due_date'      => 'nullable|date',
        ]);

        $validated['status'] = 'todo';

        Task::create($validated);

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $task->load(['assignment.serviceRequest', 'assignment.assignedTo']);
        return Inertia::render('Tasks/Show', ['task' => $task]);
    }

    public function edit(Task $task)
    {
        return Inertia::render('Tasks/Edit', [
            'task'        => $task,
            'assignments' => Assignment::with(['serviceRequest.client', 'assignedTo'])->get(),
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status'   => 'sometimes|in:todo,in_progress,review,completed',
            'priority' => 'sometimes|in:low,medium,high',
            'due_date' => 'sometimes|nullable|date',
            'title'    => 'sometimes|string|max:255',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $task->fill($validated);
        if (!$task->isDirty()) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        $task->save();

        return redirect()->back()->with('success', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }
}
