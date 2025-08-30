<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $query = Project::query();

        $sortField = request('sort_field', 'created_at');
        $sortDirection = request('sort_direction', 'desc');

        if (request('name')) {
            $query->where('name', 'like', "%" . (request('name')) . "%");
        }

        if (request('status')) {
            $query->where('status', (request('status')));
        }

        // If user is not admin, only show assigned projects
        if (!$user->isAdmin()) {
            $query->whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $projects = $query->orderBy($sortField, $sortDirection)->paginate(10)->onEachSide(1);
        return inertia('Project/Index', [
            'projects' => ProjectResource::collection($projects),
            'queryParams' => request()->query() ?: null,
            'success' => session('success'),
            'isAdmin' => $user->isAdmin()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Only admins can create projects
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        $users = User::where('role', 'user')->orderBy('name', 'asc')->get();
        return inertia('Project/Create', [
            'users' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        // Only admins can create projects
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $image = $data['image'] ?? null;

        if ($image) {
            $data['image_path'] = $image->store('project/' . Str::random(), 'public');
        }

        $project = Project::create($data);

        // Assign users to the project if selected
        if (isset($data['assigned_users']) && is_array($data['assigned_users'])) {
            $project->assignedUsers()->attach($data['assigned_users']);
        }

        return to_route('project.index')->with('success', 'Project Created');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $user = Auth::user();
        
        // If user is not admin, check if they are assigned to this project
        if (!$user->isAdmin()) {
            if (!$project->assignedUsers()->where('user_id', $user->id)->exists()) {
                abort(403, 'Access denied. You are not assigned to this project.');
            }
        }

        $query = $project->tasks();

        $sortField = request("sort_field", 'created_at');
        $sortDirection = request("sort_direction", "desc");

        if (request('name')) {
            $query->where('name', 'like', '%' . request('name') . "%");
        }

        if (request('status')) {
            $query->where('status', request('status'));
        }

        // If user is not admin, only show tasks assigned to them
        if (!$user->isAdmin()) {
            $query->where('assigned_user_id', $user->id);
        }

        $tasks = $query->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->onEachSide(1);

        return inertia('Project/Show', [
            'project' => new ProjectResource($project),
            "tasks" => TaskResource::collection($tasks),
            'queryParams' => request()->query() ?: null,
            'isAdmin' => $user->isAdmin()
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $user = Auth::user();
        
        // Only admins can edit projects
        if (!$user->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        $users = User::where('role', 'user')->orderBy('name', 'asc')->get();
        return inertia('Project/Edit', [
            'project' => new ProjectResource($project),
            'users' => $users
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $user = Auth::user();
        
        // Only admins can update projects
        if (!$user->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        $data = $request->validated();
        $image = $data['image'] ?? null;
        $data['updated_by'] = Auth::id();
        
        if ($image) {
            if ($project->image_path) {
                Storage::disk('public')->deleteDirectory(dirname($project->image_path));
            }
            $data['image_path'] = $image->store('project/' . Str::random(), 'public');
        }
        
        $project->update($data);

        // Update assigned users
        if (isset($data['assigned_users'])) {
            $project->assignedUsers()->sync($data['assigned_users']);
        }

        return to_route('project.index')
            ->with('success', "Project \"$project->name\" was updated");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $user = Auth::user();
        
        // Only admins can delete projects
        if (!$user->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        $name = $project->name;
        $project->delete();
        if ($project->image_path) {
            Storage::disk('public')->deleteDirectory(dirname($project->image_path));
        }
        return to_route('project.index')
            ->with('success', "Project \"$name\" was deleted");
    }
}
