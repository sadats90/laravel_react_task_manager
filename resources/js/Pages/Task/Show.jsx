import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link } from "@inertiajs/react";
import {
  TASK_PRIORITY_CLASS_MAP,
  TASK_PRIORITY_TEXT_MAP,
  TASK_STATUS_CLASS_MAP,
  TASK_STATUS_TEXT_MAP,
} from "@/constants.jsx";
import TimeTracker from "@/Components/TimeTracker";
import ProgressBar from "@/Components/ProgressBar";
import SubTasks from "@/Components/SubTasks";

export default function Show({ auth, task, isAdmin }) {
  return (
    <AuthenticatedLayout
      user={auth.user}
      header={
        <div className="flex items-center justify-between">
          <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {`Task "${task.name}"`}
          </h2>
          <Link
            href={route("task.edit", task.id)}
            className="bg-emerald-500 py-1 px-3 text-white rounded shadow transition-all hover:bg-emerald-600"
          >
            Edit
          </Link>
        </div>
      }
    >
      <Head title={`Task "${task.name}"`} />
      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            {task.image_path && (
              <div>
                <img
                  src={task.image_path}
                  alt=""
                  className="w-full h-64 object-cover"
                />
              </div>
            )}
            <div className="p-6 text-gray-900 dark:text-gray-100">
              <div className="grid gap-1 grid-cols-2 mt-2">
                <div>
                  <div>
                    <label className="font-bold text-lg">Task ID</label>
                    <p className="mt-1">{task.id}</p>
                  </div>
                  <div className="mt-4">
                    <label className="font-bold text-lg">Task Name</label>
                    <p className="mt-1">{task.name}</p>
                  </div>

                  <div className="mt-4">
                    <label className="font-bold text-lg">Task Status</label>
                    <p className="mt-1">
                      <span
                        className={
                          "px-2 py-1 rounded text-white " +
                          TASK_STATUS_CLASS_MAP[task.status]
                        }
                      >
                        {TASK_STATUS_TEXT_MAP[task.status]}
                      </span>
                    </p>
                  </div>

                  <div className="mt-4">
                    <label className="font-bold text-lg">Task Priority</label>
                    <p className="mt-1">
                      <span
                        className={
                          "px-2 py-1 rounded text-white " +
                          TASK_PRIORITY_CLASS_MAP[task.priority]
                        }
                      >
                        {TASK_PRIORITY_TEXT_MAP[task.priority]}
                      </span>
                    </p>
                  </div>
                  <div className="mt-4">
                    <label className="font-bold text-lg">Created By</label>
                    <p className="mt-1">{task.createdBy?.name || 'Unknown'}</p>
                  </div>
                </div>
                <div>
                  <div>
                    <label className="font-bold text-lg">Due Date</label>
                    <p className="mt-1">{task.due_date || 'Not set'}</p>
                  </div>
                  <div className="mt-4">
                    <label className="font-bold text-lg">Create Date</label>
                    <p className="mt-1">{task.created_at}</p>
                  </div>
                  <div className="mt-4">
                    <label className="font-bold text-lg">Updated By</label>
                    <p className="mt-1">{task.updatedBy?.name || 'Unknown'}</p>
                  </div>
                  <div className="mt-4">
                    <label className="font-bold text-lg">Project</label>
                    <p className="mt-1">
                      {task.project ? (
                        <Link
                          href={route("project.show", task.project.id)}
                          className="hover:underline"
                        >
                          {task.project.name}
                        </Link>
                      ) : (
                        'No project assigned'
                      )}
                    </p>
                  </div>
                  <div className="mt-4">
                    <label className="font-bold text-lg">Assigned User</label>
                    <p className="mt-1">{task.assignedUser?.name || 'Unassigned'}</p>
                  </div>
                </div>
              </div>

              <div className="mt-4">
                <label className="font-bold text-lg">Task Description</label>
                <p className="mt-1">{task.description || 'No description'}</p>
              </div>

              {/* Time Tracking Summary */}
              {(task.formatted_total_time !== '0m' || task.formatted_total_subtask_time !== '0m') && (
                <div className="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <h3 className="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">Time Tracking Summary</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {task.formatted_total_time !== '0m' && (
                      <div className="flex items-center justify-between p-3 bg-white dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Time on this task</span>
                        <span className="text-sm font-bold text-blue-600 dark:text-blue-400">
                          ⏱️ {task.formatted_total_time}
                        </span>
                      </div>
                    )}
                    {task.formatted_total_subtask_time !== '0m' && (
                      <div className="flex items-center justify-between p-3 bg-white dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Time on sub-tasks</span>
                        <span className="text-sm font-bold text-green-600 dark:text-green-400">
                          ⏱️ {task.formatted_total_subtask_time}
                        </span>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* Progress Bar */}
              <div className="mt-6">
                <ProgressBar task={task} isAdmin={isAdmin} />
              </div>

              {/* Time Tracker */}
              <div className="mt-6">
                <TimeTracker task={task} isAdmin={isAdmin} />
              </div>

              {/* Sub-tasks */}
              <div className="mt-6">
                <SubTasks task={task} isAdmin={isAdmin} />
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}