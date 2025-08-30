import React from 'react';
import { Link } from '@inertiajs/react';
import TimeTracker from './TimeTracker';

export default function SubTasks({ task, isAdmin }) {
    if (!task.subtasks || task.subtasks.length === 0) {
        return (
            <div className="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
                <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Sub-tasks</h3>
                <p className="text-gray-500 dark:text-gray-400 text-sm">No sub-tasks created yet.</p>
                {isAdmin && (
                    <Link
                        href={route('task.create', { parent_task_id: task.id })}
                        className="inline-block mt-3 bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors"
                    >
                        Add Sub-task
                    </Link>
                )}
            </div>
        );
    }

    const completedSubtasks = task.subtasks.filter(st => st.status === 'completed').length;
    const totalSubtasks = task.subtasks.length;
    const progressPercentage = totalSubtasks > 0 ? Math.round((completedSubtasks / totalSubtasks) * 100) : 0;

    // Use backend-calculated total subtask time
    const totalSubtaskTime = task.total_subtask_time || 0;
    const formattedTotalSubtaskTime = task.formatted_total_subtask_time || '0m';

    return (
        <div className="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Sub-tasks</h3>
                {isAdmin && (
                    <Link
                        href={route('task.create', { parent_task_id: task.id })}
                        className="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition-colors"
                    >
                        Add Sub-task
                    </Link>
                )}
            </div>

            <div className="mb-4">
                <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium text-gray-900 dark:text-gray-100">{progressPercentage}%</span>
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                        {completedSubtasks} of {totalSubtasks} completed
                    </span>
                </div>
                <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                        className="bg-green-500 h-2 rounded-full transition-all duration-300"
                        style={{ width: `${progressPercentage}%` }}
                    ></div>
                </div>
            </div>

            {/* Time tracking summary */}
            {totalSubtaskTime > 0 && (
                <div className="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium text-blue-800 dark:text-blue-200">Total Time on Sub-tasks</span>
                        <span className="text-sm font-bold text-blue-900 dark:text-blue-100">
                            ⏱️ {formattedTotalSubtaskTime}
                        </span>
                    </div>
                </div>
            )}

            <div className="space-y-3">
                {task.subtasks.map((subtask) => (
                    <div
                        key={subtask.id}
                        className={`p-4 rounded-lg border ${
                            subtask.status === 'completed'
                                ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700'
                                : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600'
                        }`}
                    >
                        <div className="flex items-start justify-between mb-3">
                            <div className="flex items-center space-x-3">
                                <div className={`w-4 h-4 rounded-full border-2 flex items-center justify-center ${
                                    subtask.status === 'completed'
                                        ? 'bg-green-500 border-green-500'
                                        : 'border-gray-300 dark:border-gray-500'
                                }`}>
                                    {subtask.status === 'completed' && (
                                        <svg className="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                        </svg>
                                    )}
                                </div>
                                <div>
                                    <h4 className={`font-medium ${
                                        subtask.status === 'completed' 
                                            ? 'line-through text-gray-500 dark:text-gray-400' 
                                            : 'text-gray-900 dark:text-gray-100'
                                    }`}>
                                        {subtask.name}
                                    </h4>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {subtask.assignedUser?.name} • {subtask.priority}
                                    </p>
                                    {subtask.formatted_total_time && subtask.formatted_total_time !== '0m' && (
                                        <p className="text-xs text-blue-600 dark:text-blue-400 font-medium mt-1">
                                            ⏱️ {subtask.formatted_total_time} spent
                                        </p>
                                    )}
                                </div>
                            </div>
                            <div className="flex items-center space-x-2">
                                <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                    subtask.status === 'completed'
                                        ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'
                                        : subtask.status === 'in_progress'
                                        ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
                                }`}>
                                    {subtask.status.replace('_', ' ')}
                                </span>
                                <Link
                                    href={route('task.show', subtask.id)}
                                    className="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm"
                                >
                                    View
                                </Link>
                            </div>
                        </div>
                        
                        {/* Time Tracker for each subtask */}
                        <div className="border-t border-gray-200 dark:border-gray-600 pt-3">
                            <TimeTracker task={subtask} isAdmin={isAdmin} compact={true} />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
} 