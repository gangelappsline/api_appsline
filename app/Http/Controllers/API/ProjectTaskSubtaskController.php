<?php

namespace App\Http\Controllers\API;

use App\Models\ProjectTask;
use Illuminate\Http\Request;
use App\Http\Resources\ProjectTaskResource;
use App\Services\TaskNotificationService;

class ProjectTaskSubtaskController extends BaseController
{
    public function store(Request $request, $id, $task_id, TaskNotificationService $notifications)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'estimated_hours' => 'nullable|numeric',
        ]);

        $t = ProjectTask::find($task_id);

        if(!$t){
            return $this->sendError("Parent task not found.", [], 404);
        }

        $task = new ProjectTask();
        $task->project_id = $id;
        $task->title = $validated['title'];
        $task->task_id = $task_id;
        $task->description = $validated['description'] ?? null;
        $task->status = 'pending';
        $task->due_date = $t->due_date ?? null;
        // Por defecto la subtarea es para quien la crea; si se asigna a
        // otra persona, esa persona recibe el correo de asignación.
        $task->assigned_to = $validated['assigned_to'] ?? auth()->id();
        $task->estimated_hours = $validated['estimated_hours'] ?? null;
        $task->actual_hours = null;
        $task->priority = $t->priority ?? null;
        $task->category = $t->category ?? null;
        $task->tags = $t->tags ?? null;
        $task->created_by = auth()->id();
        $task->save();

        $notifications->notifyTaskAssigned($task, auth()->user());

        return $this->sendResponse(new ProjectTaskResource($task), "Task created successfully.");
    }


    public function destroy(ProjectTask $projectTask)
    {
        $projectTask->delete();
        return $this->sendResponse(null, "Task deleted successfully.");
    }
}
