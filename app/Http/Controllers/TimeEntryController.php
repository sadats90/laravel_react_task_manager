<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimeEntryController extends Controller
{
    /**
     * Start timer for a task
     */
    public function start(Request $request, Task $task)
    {
        $user = Auth::user();
        
        // Check if user can access this task
        if (!$user->isAdmin() && $task->assigned_user_id !== $user->id) {
            abort(403, 'Access denied. You are not assigned to this task.');
        }

        // Check if there's already a running timer for this task
        $runningEntry = $task->runningTimeEntry();
        if ($runningEntry) {
            return response()->json([
                'message' => 'Timer is already running for this task',
                'timeEntry' => $runningEntry
            ], 400);
        }

        // Create new time entry
        $timeEntry = TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'description' => $request->input('description')
        ]);

        return response()->json([
            'message' => 'Timer started successfully',
            'timeEntry' => $timeEntry
        ]);
    }

    /**
     * Stop timer for a task
     */
    public function stop(Request $request, Task $task)
    {
        $user = Auth::user();
        
        // Check if user can access this task
        if (!$user->isAdmin() && $task->assigned_user_id !== $user->id) {
            abort(403, 'Access denied. You are not assigned to this task.');
        }

        $runningEntry = $task->runningTimeEntry();
        if (!$runningEntry) {
            return response()->json([
                'message' => 'No running timer found for this task'
            ], 400);
        }

        $runningEntry->stop();

        return response()->json([
            'message' => 'Timer stopped successfully',
            'timeEntry' => $runningEntry,
            'totalTime' => $task->formatted_total_time
        ]);
    }

    /**
     * Get time entries for a task
     */
    public function index(Task $task)
    {
        $user = Auth::user();
        
        // Check if user can access this task
        if (!$user->isAdmin() && $task->assigned_user_id !== $user->id) {
            abort(403, 'Access denied. You are not assigned to this task.');
        }

        $timeEntries = $task->timeEntries()
            ->with('user')
            ->orderBy('started_at', 'desc')
            ->paginate(10);

        return response()->json([
            'timeEntries' => $timeEntries,
            'totalTime' => $task->formatted_total_time,
            'isRunning' => $task->runningTimeEntry() !== null
        ]);
    }

    /**
     * Update time entry description
     */
    public function update(Request $request, TimeEntry $timeEntry)
    {
        $user = Auth::user();
        
        // Check if user owns this time entry
        if ($timeEntry->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Access denied.');
        }

        $timeEntry->update([
            'description' => $request->input('description')
        ]);

        return response()->json([
            'message' => 'Time entry updated successfully',
            'timeEntry' => $timeEntry
        ]);
    }

    /**
     * Delete time entry
     */
    public function destroy(TimeEntry $timeEntry)
    {
        $user = Auth::user();
        
        // Check if user owns this time entry
        if ($timeEntry->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Access denied.');
        }

        $timeEntry->delete();

        return response()->json([
            'message' => 'Time entry deleted successfully'
        ]);
    }

    /**
     * Get current timer status for all user's tasks
     */
    public function getCurrentTimers()
    {
        $user = Auth::user();
        
        $runningTasks = Task::whereHas('timeEntries', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->whereNull('stopped_at');
        })->with(['runningTimeEntry', 'project'])->get();

        return response()->json([
            'runningTasks' => $runningTasks
        ]);
    }
} 