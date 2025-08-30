<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TaskResource extends JsonResource
{

    public static $wrap = false;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? '',
            'description' => $this->description ?? '',
            'created_at' => $this->created_at ? (new Carbon($this->created_at))->format('Y-m-d') : null,
            'due_date' => $this->due_date ? (new Carbon($this->due_date))->format('Y-m-d') : null,
            'status' => $this->status ?? 'pending',
            'priority' => $this->priority ?? 'medium',
            'progress' => $this->progress ?? 0,
            'parent_task_id' => $this->parent_task_id,
            'image_path' => $this->image_path && !(str_starts_with($this->image_path, 'http')) ?
                Storage::url($this->image_path) : '',
            'project_id' => $this->project_id,
            'project' => $this->when($this->project_id, function() {
                return $this->project ? new ProjectResource($this->project) : null;
            }),
            'assigned_user_id' => $this->assigned_user_id,
            'assignedUser' => $this->when($this->relationLoaded('assignedUser') && $this->assignedUser, function() {
                return new UserResource($this->assignedUser);
            }),
            'createdBy' => $this->when($this->relationLoaded('createdBy') && $this->createdBy, function() {
                return new UserResource($this->createdBy);
            }),
            'updatedBy' => $this->when($this->relationLoaded('updatedBy') && $this->updatedBy, function() {
                return new UserResource($this->updatedBy);
            }),
            
            // Sub-tasks and hierarchy
            'parent' => $this->when($this->relationLoaded('parent') && $this->parent, function() {
                return new TaskResource($this->parent);
            }),
            'subtasks' => $this->when($this->relationLoaded('subtasks'), function() {
                return TaskResource::collection($this->subtasks ?? collect());
            }),
            'subtasks_count' => $this->subtasks()->count(),
            'is_parent' => $this->isParent(),
            'is_subtask' => $this->isSubtask(),
            
            // Time tracking
            'total_time_spent' => $this->total_time_spent ?? 0,
            'formatted_total_time' => $this->formatted_total_time ?? '0m',
            'time_entries' => $this->when($this->relationLoaded('timeEntries'), function() {
                return $this->timeEntries ?? collect();
            }),
            'running_time_entry' => $this->when($this->relationLoaded('runningTimeEntry'), function() {
                return $this->runningTimeEntry;
            }),
            'is_timer_running' => $this->runningTimeEntry() !== null,
            
            // Sub-task time tracking (for parent tasks)
            'total_subtask_time' => $this->total_subtask_time ?? 0,
            'formatted_total_subtask_time' => $this->formatted_total_subtask_time ?? '0m',
        ];
    }
}