<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'status',
        'priority',
        'due_date',
        'assigned_user_id',
        'created_by',
        'updated_by',
        'project_id',
        'parent_task_id',
        'progress'
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'progress' => 'integer'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get parent task
     */
    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Get sub-tasks
     */
    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Get all time entries for this task
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Get running time entry
     */
    public function runningTimeEntry()
    {
        try {
            return $this->timeEntries()->whereNull('stopped_at')->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get total time spent on this task
     */
    public function getTotalTimeSpentAttribute()
    {
        try {
            return $this->timeEntries()->sum('duration_seconds') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get formatted total time
     */
    public function getFormattedTotalTimeAttribute()
    {
        $totalSeconds = $this->total_time_spent;
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }

    /**
     * Get total time spent on all subtasks
     */
    public function getTotalSubtaskTimeAttribute()
    {
        try {
            return $this->subtasks()->sum('total_time_spent') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get formatted total subtask time
     */
    public function getFormattedTotalSubtaskTimeAttribute()
    {
        $totalSeconds = $this->total_subtask_time;
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }

    /**
     * Calculate progress based on sub-tasks
     */
    public function calculateProgress()
    {
        try {
            if ($this->subtasks()->count() === 0) {
                return $this->progress ?? 0;
            }

            $completedSubtasks = $this->subtasks()->where('status', 'completed')->count();
            $totalSubtasks = $this->subtasks()->count();

            if ($totalSubtasks === 0) {
                return 0;
            }

            return round(($completedSubtasks / $totalSubtasks) * 100);
        } catch (\Exception $e) {
            return $this->progress ?? 0;
        }
    }

    /**
     * Update progress and parent task progress
     */
    public function updateProgress($progress = null)
    {
        if ($progress === null) {
            $progress = $this->calculateProgress();
        }

        $this->progress = $progress;
        $this->save();

        // Update parent task progress if this is a sub-task
        if ($this->parent_task_id && $this->parent) {
            try {
                $this->parent->updateProgress();
            } catch (\Exception $e) {
                // Log error but don't fail
                \Log::error('Error updating parent progress: ' . $e->getMessage());
            }
        }
    }

    /**
     * Check if task is a parent task
     */
    public function isParent()
    {
        try {
            return $this->subtasks()->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if task is a sub-task
     */
    public function isSubtask()
    {
        return !is_null($this->parent_task_id);
    }

    /**
     * Get all ancestors (parent, grandparent, etc.)
     */
    public function getAncestors()
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors->reverse();
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     */
    public function getDescendants()
    {
        $descendants = collect();

        try {
            foreach ($this->subtasks as $subtask) {
                $descendants->push($subtask);
                $descendants = $descendants->merge($subtask->getDescendants());
            }
        } catch (\Exception $e) {
            // Return empty collection if error
        }

        return $descendants;
    }
}