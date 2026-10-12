<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProjectTask;
use Illuminate\Console\View\Components\Task;
use Illuminate\Http\Request;
use App\Http\Resources\ProjectTaskResource;
use Illuminate\Support\Facades\Mail;
use App\Mail\TaskAssigned;
use App\Models\User;
use App\Models\ProjectTaskComment;
use Carbon\Carbon;

class ProjectTaskSubtaskController extends BaseController
{
    public function store(Request $request, $id, $task_id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
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
        $task->assigned_to = auth()->id();
        $task->estimated_hours = $validated['estimated_hours'] ?? null;
        $task->actual_hours = $validated['actual_hours'] ?? null;
        $task->priority = $t->priority ?? null;
        $task->category = $t->category ?? null;
        $task->tags = $t->tags ?? null;
        $task->created_by = auth()->id();
        $task->save();

        // enviar correo si la tarea fue asignada a un usuario
        if ($task->assigned_to) {
            $assignee = User::find($task->assigned_to);
            if ($assignee && $assignee->email) {
                $meta = [
                    'assigneeName' => $assignee->name,
                    'assignerName' => auth()->user()?->name,
                    'taskUrl' => url("/projects/{$task->project_id}/tasks/{$task->id}"),
                ];
                // usa queue() si tienes colas; de lo contrario cambia a send()
                Mail::to($assignee->email)->queue(new TaskAssigned($task, $meta));
            }
        }

        return $this->sendResponse(new ProjectTaskResource($task), "Task created successfully.");
    }


    public function destroy(ProjectTask $projectTask)
    {
        $projectTask->delete();
        return $this->sendResponse(null, "Task deleted successfully.");
    }
}
