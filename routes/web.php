<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Project routes - only admins can create/edit/delete
    Route::resource('project', ProjectController::class);
    
    Route::get('/task/my-tasks', [TaskController::class, 'myTasks'])
        ->name('task.myTasks');
    Route::resource('task', TaskController::class);
    
    // Sub-tasks routes
    Route::get('/task/{task}/subtasks', [TaskController::class, 'subtasks'])
        ->name('task.subtasks');
    Route::patch('/task/{task}/progress', [TaskController::class, 'updateProgress'])
        ->name('task.progress');
    
    // Time tracking routes
    Route::prefix('time-entries')->name('time-entries.')->group(function () {
        Route::post('/{task}/start', [TimeEntryController::class, 'start'])->name('start');
        Route::post('/{task}/stop', [TimeEntryController::class, 'stop'])->name('stop');
        Route::get('/{task}', [TimeEntryController::class, 'index'])->name('index');
        Route::put('/{timeEntry}', [TimeEntryController::class, 'update'])->name('update');
        Route::delete('/{timeEntry}', [TimeEntryController::class, 'destroy'])->name('destroy');
        Route::get('/current-timers', [TimeEntryController::class, 'getCurrentTimers'])->name('current');
    });
    
    // User management - only admins can access
    Route::middleware('admin')->group(function () {
        Route::resource('user', UserController::class);
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';