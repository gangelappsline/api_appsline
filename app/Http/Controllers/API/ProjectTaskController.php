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
use Carbon\Carbon;

class ProjectTaskController extends BaseController
{
    public function index($id)
    {
        $tasks = ProjectTask::where('project_id', $id)->get();
        return $this->sendResponse(["tasks"=>ProjectTaskResource::collection($tasks)], "Tasks");
    }

    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'estimated_hours' => 'nullable|numeric',
            'actual_hours' => 'nullable|numeric',
            'priority' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:255',
        ]);

        $task = new ProjectTask();
        $task->project_id = $id;
        $task->title = $validated['title'];
        $task->description = $validated['description'];
        $task->status = 'pending';
        $task->due_date = $validated['due_date'];
        $task->assigned_to = $validated['assigned_to'];
        $task->estimated_hours = $validated['estimated_hours'] ?? null;
        $task->actual_hours = $validated['actual_hours'] ?? null;
        $task->priority = $validated['priority'] ?? null;
        $task->category = $validated['category'] ?? null;
        $task->tags = $validated['tags'] ?? null;
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

    public function show($project_id, $id)
    {
        $projectTask = ProjectTask::where('id', $id)->where('project_id', $project_id)->firstOrFail();
        return $this->sendResponse(new ProjectTaskResource($projectTask), "Task retrieved successfully.");
    }

    public function update(Request $request, $project_id, $id)
    {
        $projectTask = ProjectTask::where('id', $id)->where('project_id', $project_id)->firstOrFail();

        $validated = $request->validate([
            'project_id' => 'sometimes|integer|exists:projects,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:pending,in_progress,completed',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'estimated_hours' => 'nullable|numeric',
            'actual_hours' => 'nullable|numeric',
            'priority' => 'nullable|string|max:255',
            'attachment' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:255',
            'completed_at' => 'nullable|date',
            'created_by' => 'sometimes|integer|exists:users,id',
        ]);

        // Si se está marcando como completed y antes no lo estaba, calcular actual_hours y guardar completed_at
        if (isset($validated['status']) && $validated['status'] === 'completed' && $projectTask->status !== 'completed') {
            $createdAt = $projectTask->created_at ?? Carbon::now();
            $now = Carbon::now();

            // calcular horas transcurridas con decimales (segundos -> horas)
            $seconds = $now->diffInSeconds($createdAt);
            $hours = round($seconds / 3600, 2);

            $validated['actual_hours'] = $hours;
            $validated['completed_at'] = $now->toDateTimeString();
        }

        $projectTask->update($validated);
        $projectTask->refresh();

        return $this->sendResponse(new ProjectTaskResource($projectTask), "Task updated successfully.");
    }

    public function destroy(ProjectTask $projectTask)
    {
        $projectTask->delete();
        return $this->sendResponse(null, "Task deleted successfully.");
    }
}
