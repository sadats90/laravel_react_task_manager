import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';

export default function TimeTracker({ task, isAdmin, compact = false }) {
    const [isRunning, setIsRunning] = useState(task.is_timer_running);
    const [currentTime, setCurrentTime] = useState(0);
    const [description, setDescription] = useState('');

    useEffect(() => {
        let interval;
        if (isRunning) {
            interval = setInterval(() => {
                setCurrentTime(prev => prev + 1);
            }, 1000);
        }
        return () => clearInterval(interval);
    }, [isRunning]);

    const formatTime = (seconds) => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    const startTimer = async () => {
        try {
            const response = await axios.post(route('time-entries.start', task.id), {
                description: description
            });
            setIsRunning(true);
            setCurrentTime(0);
            setDescription('');
        } catch (error) {
            console.error('Error starting timer:', error);
        }
    };

    const stopTimer = async () => {
        try {
            const response = await axios.post(route('time-entries.stop', task.id));
            setIsRunning(false);
            setCurrentTime(0);
            // Refresh the page to update the total time
            router.reload();
        } catch (error) {
            console.error('Error stopping timer:', error);
        }
    };

    if (compact) {
        return (
            <div className="flex items-center justify-between">
                <div className="flex items-center space-x-3">
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                        Total: {task.formatted_total_time}
                    </span>
                    {isRunning && (
                        <span className="text-sm text-blue-600 dark:text-blue-400 font-medium">
                            Running: {formatTime(currentTime)}
                        </span>
                    )}
                </div>
                <div className="flex items-center space-x-2">
                    {!isRunning ? (
                        <button
                            onClick={startTimer}
                            className="bg-green-500 text-white py-1 px-3 rounded text-xs hover:bg-green-600 transition-colors"
                        >
                            Start
                        </button>
                    ) : (
                        <button
                            onClick={stopTimer}
                            className="bg-red-500 text-white py-1 px-3 rounded text-xs hover:bg-red-600 transition-colors"
                        >
                            Stop
                        </button>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
            <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Time Tracker</h3>
            
            <div className="mb-4">
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">Total Time Spent: {task.formatted_total_time}</p>
                
                {isRunning && (
                    <div className="bg-blue-100 dark:bg-blue-900/30 p-3 rounded">
                        <p className="text-blue-800 dark:text-blue-200 font-medium">Timer Running</p>
                        <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">{formatTime(currentTime)}</p>
                    </div>
                )}
            </div>

            {!isRunning ? (
                <div className="space-y-3">
                    <textarea
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        placeholder="What are you working on?"
                        className="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        rows="2"
                    />
                    <button
                        onClick={startTimer}
                        className="w-full bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 transition-colors"
                    >
                        Start Timer
                    </button>
                </div>
            ) : (
                <button
                    onClick={stopTimer}
                    className="w-full bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 transition-colors"
                >
                    Stop Timer
                </button>
            )}
        </div>
    );
} 