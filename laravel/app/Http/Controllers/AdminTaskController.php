<?php

namespace App\Http\Controllers;

use App\Models\AdminTask;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class AdminTaskController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = AdminTask::with(['assignedTo', 'assignedBy']);

        // SSG organizer only sees own tasks
        if ($user->isOrganizer()) {
            $query->where('assigned_to', $user->id);
        }

        $tasks = $query->latest()->get();

        // Get organizers for assignment dropdown
        $organizers = User::whereIn('type_id', [6])->where('status', 'Active')->get();

        return view('admin.manage-tasks', compact('tasks', 'organizers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority'    => 'required|in:low,normal,high,urgent',
            'due_date'    => 'nullable|date',
        ]);

        $validated['assigned_by'] = auth()->id();
        $task = AdminTask::create($validated);

        ActivityLogger::log('create', 'task', $task->id, "Assigned task: {$task->title}");

        return redirect()->back()->with('success', 'Task assigned!');
    }

    public function update(Request $request, AdminTask $task)
    {
        $validated = $request->validate([
            'status'   => 'sometimes|in:pending,in_progress,completed,cancelled',
            'priority' => 'sometimes|in:low,normal,high,urgent',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $task->update($validated);

        ActivityLogger::log('update', 'task', $task->id, "Updated task status: {$task->status}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Task updated.');
    }

    public function destroy(AdminTask $task)
    {
        $title = $task->title;
        $task->delete();
        ActivityLogger::log('delete', 'task', $task->id ?? 0, "Deleted task: {$title}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Task deleted.');
    }
}
