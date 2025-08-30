<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'started_at',
        'stopped_at',
        'duration_seconds',
        'description'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get duration in hours
     */
    public function getDurationHoursAttribute()
    {
        return round($this->duration_seconds / 3600, 2);
    }

    /**
     * Check if timer is running
     */
    public function isRunning()
    {
        return $this->stopped_at === null;
    }

    /**
     * Stop the timer
     */
    public function stop()
    {
        if ($this->isRunning()) {
            $this->stopped_at = now();
            $this->duration_seconds = $this->started_at->diffInSeconds($this->stopped_at);
            $this->save();
        }
    }
} 