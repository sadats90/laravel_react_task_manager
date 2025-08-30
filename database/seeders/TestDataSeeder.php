<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if not exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'admin'
            ]
        );

        // Create regular user if not exists
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'user'
            ]
        );

        // Create a test project
        $project = Project::firstOrCreate(
            ['name' => 'Test Project'],
            [
                'description' => 'This is a test project for demonstration',
                'status' => 'in_progress',
                'due_date' => now()->addDays(30),
                'created_by' => $admin->id,
                'updated_by' => $admin->id
            ]
        );

        // Assign users to the project
        $project->assignedUsers()->sync([$admin->id, $user->id]);

        // Create a main task
        $mainTask = Task::firstOrCreate(
            ['name' => 'Main Task'],
            [
                'description' => 'This is a main task with sub-tasks',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_date' => now()->addDays(7),
                'assigned_user_id' => $user->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'project_id' => $project->id,
                'progress' => 30
            ]
        );

        // Create sub-tasks
        $subTask1 = Task::firstOrCreate(
            ['name' => 'Sub-task 1'],
            [
                'description' => 'First sub-task',
                'status' => 'completed',
                'priority' => 'medium',
                'due_date' => now()->addDays(3),
                'assigned_user_id' => $user->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'project_id' => $project->id,
                'parent_task_id' => $mainTask->id,
                'progress' => 100
            ]
        );

        $subTask2 = Task::firstOrCreate(
            ['name' => 'Sub-task 2'],
            [
                'description' => 'Second sub-task',
                'status' => 'in_progress',
                'priority' => 'medium',
                'due_date' => now()->addDays(5),
                'assigned_user_id' => $user->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'project_id' => $project->id,
                'parent_task_id' => $mainTask->id,
                'progress' => 50
            ]
        );

        $subTask3 = Task::firstOrCreate(
            ['name' => 'Sub-task 3'],
            [
                'description' => 'Third sub-task',
                'status' => 'pending',
                'priority' => 'low',
                'due_date' => now()->addDays(10),
                'assigned_user_id' => $user->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'project_id' => $project->id,
                'parent_task_id' => $mainTask->id,
                'progress' => 0
            ]
        );

        // Create another simple task
        $simpleTask = Task::firstOrCreate(
            ['name' => 'Simple Task'],
            [
                'description' => 'This is a simple task without sub-tasks',
                'status' => 'pending',
                'priority' => 'medium',
                'due_date' => now()->addDays(14),
                'assigned_user_id' => $user->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'project_id' => $project->id,
                'progress' => 0
            ]
        );

        echo "Test data created successfully!\n";
        echo "Admin: {$admin->email} (password: password)\n";
        echo "User: {$user->email} (password: password)\n";
        echo "Project: {$project->name}\n";
        echo "Main Task: {$mainTask->name} with 3 sub-tasks\n";
        echo "Simple Task: {$simpleTask->name}\n";
    }
} 