import React, { useState } from 'react';
import axios from 'axios';

export default function ProgressBar({ task, isAdmin, onProgressUpdate }) {
    const [progress, setProgress] = useState(task.progress || 0);
    const [isEditing, setIsEditing] = useState(false);

    const updateProgress = async (newProgress) => {
        try {
            const response = await axios.patch(route('task.progress', task.id), {
                progress: newProgress
            });
            setProgress(newProgress);
            if (onProgressUpdate) {
                onProgressUpdate(newProgress);
            }
        } catch (error) {
            console.error('Error updating progress:', error);
        }
    };

    const getProgressColor = (progress) => {
        if (progress >= 80) return 'bg-green-500';
        if (progress >= 50) return 'bg-yellow-500';
        if (progress >= 20) return 'bg-orange-500';
        return 'bg-red-500';
    };

    const getProgressText = (progress) => {
        if (progress === 100) return 'Completed';
        if (progress >= 80) return 'Almost Done';
        if (progress >= 50) return 'In Progress';
        if (progress >= 20) return 'Started';
        return 'Not Started';
    };

    return (
        <div className="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
            <div className="flex items-center justify-between mb-3">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Progress</h3>
                {isAdmin && (
                    <button
                        onClick={() => setIsEditing(!isEditing)}
                        className="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm"
                    >
                        {isEditing ? 'Cancel' : 'Edit'}
                    </button>
                )}
            </div>

            {isEditing ? (
                <div className="space-y-3">
                    <div className="flex items-center space-x-3">
                        <input
                            type="range"
                            min="0"
                            max="100"
                            value={progress}
                            onChange={(e) => setProgress(parseInt(e.target.value))}
                            className="flex-1"
                        />
                        <span className="text-sm font-medium w-12 text-gray-900 dark:text-gray-100">{progress}%</span>
                    </div>
                    <div className="flex space-x-2">
                        <button
                            onClick={() => updateProgress(progress)}
                            className="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600"
                        >
                            Save
                        </button>
                        <button
                            onClick={() => setIsEditing(false)}
                            className="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            ) : (
                <div>
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-sm font-medium text-gray-900 dark:text-gray-100">{progress}%</span>
                        <span className="text-sm text-gray-600 dark:text-gray-400">{getProgressText(progress)}</span>
                    </div>
                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div
                            className={`h-2.5 rounded-full ${getProgressColor(progress)} transition-all duration-300`}
                            style={{ width: `${progress}%` }}
                        ></div>
                    </div>
                    
                    {task.subtasks && task.subtasks.length > 0 && (
                        <div className="mt-3">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Sub-tasks: {task.subtasks.filter(st => st.status === 'completed').length} / {task.subtasks.length} completed
                            </p>
                            <div className="space-y-1">
                                {task.subtasks.map((subtask) => (
                                    <div key={subtask.id} className="flex items-center space-x-2">
                                        <div className={`w-2 h-2 rounded-full ${
                                            subtask.status === 'completed' ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-500'
                                        }`}></div>
                                        <span className={`text-xs ${
                                            subtask.status === 'completed' 
                                                ? 'line-through text-gray-500 dark:text-gray-400' 
                                                : 'text-gray-700 dark:text-gray-300'
                                        }`}>
                                            {subtask.name}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
} 