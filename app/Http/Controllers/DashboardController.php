<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        
        if ($isAdmin) {
            // Admin sees all data
            $totalPendingTasks = Task::query()
                ->where('status', 'pending')
                ->count();
            $totalProgressTasks = Task::query()
                ->where('status', 'in_progress')
                ->count();
            $totalCompletedTasks = Task::query()
                ->where('status', 'completed')
                ->count();
            $totalProjects = Project::query()->count();
            
            $activeTasks = Task::query()
                ->whereIn('status', ['pending', 'in_progress'])
                ->limit(10)
                ->get();
        } else {
            // Regular users see only their assigned data
            $totalPendingTasks = Task::query()
                ->where('status', 'pending')
                ->where('assigned_user_id', $user->id)
                ->count();
            $totalProgressTasks = Task::query()
                ->where('status', 'in_progress')
                ->where('assigned_user_id', $user->id)
                ->count();
            $totalCompletedTasks = Task::query()
                ->where('status', 'completed')
                ->where('assigned_user_id', $user->id)
                ->count();
            $totalProjects = $user->assignedProjects()->count();
            
            $activeTasks = Task::query()
                ->whereIn('status', ['pending', 'in_progress'])
                ->where('assigned_user_id', $user->id)
                ->limit(10)
                ->get();
        }
        
        $activeTasks = TaskResource::collection($activeTasks);
        
        return inertia(
            'Dashboard',
            compact(
                'totalPendingTasks',
                'totalProgressTasks',
                'totalCompletedTasks',
                'totalProjects',
                'activeTasks',
                'isAdmin'
            )
        );
    }
}