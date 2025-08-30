<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $query = Task::query();

        $sortField = request("sort_field", 'created_at');
        $sortDirection = request("sort_direction", "desc");

        if (request("name")) {
            $query->where("name", "like", "%" . request("name") . "%");
        }
        if (request("status")) {
            $query->where("status", request("status"));
        }

        // If user is not admin, only show tasks assigned to them
        if (!$user->isAdmin()) {
            $query->where('assigned_user_id', $user->id);
        }

        // Only show parent tasks (not sub-tasks) in main listing
        $query->whereNull('parent_task_id');

        // Load necessary relationships
        $query->with(['project', 'createdBy', 'assignedUser']);

        $tasks = $query->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->onEachSide(1);

        return inertia("Task/Index", [
            "tasks" => TaskResource::collection($tasks),
            'queryParams' => request()->query() ?: [
                'sort_field' => 'created_at',
                'sort_direction' => 'desc',
                'name' => '',
                'status' => ''
            ],
            'success' => session('success'),
            'isAdmin' => $user->isAdmin()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $projects = Project::query()->orderBy('name', 'asc')->get();
        $users = User::query()->orderBy('name', 'asc')->get();

        // If user is not admin, only show projects they are assigned to
        if (!$user->isAdmin()) {
            $projects = $user->assignedProjects()->orderBy('name', 'asc')->get();
        }

        // Get parent tasks for sub-task creation
        $parentTasks = Task::whereNull('parent_task_id')
            ->when(!$user->isAdmin(), function($query) use ($user) {
                $query->where('assigned_user_id', $user->id);
            })
            ->orderBy('name', 'asc')
            ->get();

        return inertia("Task/Create", [
            'projects' => ProjectResource::collection($projects),
            'users' => UserResource::collection($users),
            'parentTasks' => TaskResource::collection($parentTasks),
            'isAdmin' => $user->isAdmin()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        
        // If user is not admin, ensure they can only assign tasks to themselves
        if (!$user->isAdmin()) {
            $data['assigned_user_id'] = $user->id;
        }
        
        /** @var $image \Illuminate\Http\UploadedFile */
        $image = $data['image'] ?? null;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        
        if ($image) {
            $data['image_path'] = $image->store('task/' . Str::random(), 'public');
        } 
        
        $task = Task::create($data);

        // Update parent task progress if this is a sub-task
        if (isset($data['parent_task_id']) && $task->parent) {
            $task->parent->updateProgress();
        }

        return to_route('task.index')
            ->with('success', 'Task was created');
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $user = Auth::user();
        
        // If user is not admin, check if they are assigned to this task
        if (!$user->isAdmin()) {
            if ($task->assigned_user_id !== $user->id) {
                abort(403, 'Access denied. You are not assigned to this task.');
            }
        }

        // Load relationships individually with safety checks
        $relationships = [];
        
        // Load subtasks if they exist
        if ($task->subtasks()->exists()) {
            try {
                $task->load(['subtasks' => function($query) {
                    $query->orderBy('created_at', 'desc');
                }]);
                
                // Load time entries for each subtask
                $task->subtasks->load(['timeEntries' => function($query) {
                    $query->with('user')->orderBy('started_at', 'desc');
                }]);
                
                // Load running time entries for subtasks
                foreach ($task->subtasks as $subtask) {
                    $runningEntry = $subtask->runningTimeEntry();
                    if ($runningEntry) {
                        $subtask->setRelation('runningTimeEntry', $runningEntry);
                    }
                }
                
                $relationships[] = 'subtasks';
            } catch (\Exception $e) {
                \Log::error('Error loading subtasks: ' . $e->getMessage());
            }
        }

        // Load time entries if they exist
        if ($task->timeEntries()->exists()) {
            try {
                $task->load(['timeEntries' => function($query) {
                    $query->with('user')->orderBy('started_at', 'desc');
                }]);
                $relationships[] = 'timeEntries';
            } catch (\Exception $e) {
                \Log::error('Error loading time entries: ' . $e->getMessage());
            }
        }

        // Load running time entry if it exists
        if ($task->timeEntries()->whereNull('stopped_at')->exists()) {
            try {
                $task->load(['runningTimeEntry']);
                $relationships[] = 'runningTimeEntry';
            } catch (\Exception $e) {
                \Log::error('Error loading running time entry: ' . $e->getMessage());
            }
        }

        // Load basic relationships individually
        $basicRelationships = ['project', 'assignedUser', 'createdBy', 'updatedBy'];
        
        foreach ($basicRelationships as $relation) {
            try {
                if ($task->$relation()->exists()) {
                    $task->load($relation);
                    $relationships[] = $relation;
                }
            } catch (\Exception $e) {
                \Log::error("Error loading {$relation}: " . $e->getMessage());
            }
        }

        // Load parent if it exists
        if ($task->parent_task_id) {
            try {
                $task->load('parent');
                $relationships[] = 'parent';
            } catch (\Exception $e) {
                \Log::error('Error loading parent: ' . $e->getMessage());
            }
        }

        return inertia('Task/Show', [
            'task' => new TaskResource($task),
            'isAdmin' => $user->isAdmin()
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        $user = Auth::user();
        
        // If user is not admin, check if they are assigned to this task
        if (!$user->isAdmin()) {
            if ($task->assigned_user_id !== $user->id) {
                abort(403, 'Access denied. You are not assigned to this task.');
            }
        }

        $projects = Project::query()->orderBy('name', 'asc')->get();
        $users = User::query()->orderBy('name', 'asc')->get();

        // If user is not admin, only show projects they are assigned to
        if (!$user->isAdmin()) {
            $projects = $user->assignedProjects()->orderBy('name', 'asc')->get();
        }

        // Get parent tasks for sub-task creation (exclude current task and its descendants)
        $parentTasks = Task::whereNull('parent_task_id')
            ->where('id', '!=', $task->id)
            ->when(!$user->isAdmin(), function($query) use ($user) {
                $query->where('assigned_user_id', $user->id);
            })
            ->orderBy('name', 'asc')
            ->get();

        return inertia("Task/Edit", [
            'task' => new TaskResource($task),
            'projects' => ProjectResource::collection($projects),
            'users' => UserResource::collection($users),
            'parentTasks' => TaskResource::collection($parentTasks),
            'isAdmin' => $user->isAdmin()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $user = Auth::user();
        
        // If user is not admin, check if they are assigned to this task
        if (!$user->isAdmin()) {
            if ($task->assigned_user_id !== $user->id) {
                abort(403, 'Access denied. You are not assigned to this task.');
            }
        }

        $data = $request->validated();
        $image = $data['image'] ?? null;
        $data['updated_by'] = Auth::id();
        
        if ($image) {
            if ($task->image_path) {
                Storage::disk('public')->deleteDirectory(dirname($task->image_path));
            }
            $data['image_path'] = $image->store('task/' . Str::random(), 'public');
        }
        
        $oldParentId = $task->parent_task_id;
        $task->update($data);

        // Update progress if status changed to completed
        if (isset($data['status']) && $data['status'] === 'completed') {
            $task->updateProgress(100);
        } elseif (isset($data['status']) && $data['status'] !== 'completed') {
            $task->updateProgress();
        }

        // Update parent task progress if parent changed
        if (isset($data['parent_task_id']) && $data['parent_task_id'] !== $oldParentId) {
            if ($oldParentId) {
                $oldParent = Task::find($oldParentId);
                if ($oldParent) {
                    $oldParent->updateProgress();
                }
            }
            if ($data['parent_task_id'] && $task->parent) {
                $task->parent->updateProgress();
            }
        }

        return to_route('task.index')
            ->with('success', "Task \"$task->name\" was updated");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $user = Auth::user();
        
        // If user is not admin, check if they are assigned to this task
        if (!$user->isAdmin()) {
            if ($task->assigned_user_id !== $user->id) {
                abort(403, 'Access denied. You are not assigned to this task.');
            }
        }

        $name = $task->name;
        $parentTask = $task->parent;
        
        $task->delete();
        
        if ($task->image_path) {
            Storage::disk('public')->deleteDirectory(dirname($task->image_path));
        }

        // Update parent task progress
        if ($parentTask) {
            $parentTask->updateProgress();
        }

        return to_route('task.index')
            ->with('success', "Task \"$name\" was deleted");
    }

    /**
     * Show sub-tasks for a parent task
     */
    public function subtasks(Task $task)
    {
        $user = Auth::user();
        
        // If user is not admin, check if they are assigned to this task
        if (!$user->isAdmin()) {
            if ($task->assigned_user_id !== $user->id) {
                abort(403, 'Access denied. You are not assigned to this task.');
            }
        }

        $subtasks = $task->subtasks()
            ->with(['assignedUser', 'timeEntries', 'runningTimeEntry'])
            ->orderBy('created_at', 'asc')
            ->get();

        return inertia('Task/Subtasks', [
            'parentTask' => new TaskResource($task),
            'subtasks' => TaskResource::collection($subtasks),
            'isAdmin' => $user->isAdmin()
        ]);
    }

    /**
     * Update task progress manually
     */
    public function updateProgress(Request $request, Task $task)
    {
        $user = Auth::user();
        
        // If user is not admin, check if they are assigned to this task
        if (!$user->isAdmin()) {
            if ($task->assigned_user_id !== $user->id) {
                abort(403, 'Access denied. You are not assigned to this task.');
            }
        }

        $request->validate([
            'progress' => 'required|integer|min:0|max:100'
        ]);

        $task->updateProgress($request->progress);

        return response()->json([
            'message' => 'Progress updated successfully',
            'progress' => $task->progress
        ]);
    }

    public function myTasks()
    {
        $user = auth()->user();
        $query = Task::query()->where('assigned_user_id', $user->id);

        $sortField = request("sort_field", 'created_at');
        $sortDirection = request("sort_direction", "desc");

        if (request("name")) {
            $query->where("name", "like", "%" . request("name") . "%");
        }
        if (request("status")) {
            $query->where("status", request("status"));
        }

        // Only show parent tasks (not sub-tasks) in my tasks
        $query->whereNull('parent_task_id');

        $tasks = $query->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->onEachSide(1);

        return inertia("Task/Index", [
            "tasks" => TaskResource::collection($tasks),
            'queryParams' => request()->query() ?: null,
            'success' => session('success'),
            'isAdmin' => $user->isAdmin()
        ]);
    }
}