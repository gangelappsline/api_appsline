<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProjectTaskComment;
use App\Http\Resources\ProjectTaskCommentResource;
use Illuminate\Http\Request;

class ProjectTaskCommentController extends Controller
{
    // GET /projects/{project_id}/tasks/{task_id}/comments
    public function index($project_id, $task_id)
    {
        $comments = ProjectTaskComment::where('project_task_id', $task_id)
            ->latest()
            ->get();

        return ProjectTaskCommentResource::collection($comments);
    }

    // POST /projects/{project_id}/tasks/{task_id}/comments
    public function store(Request $request, $project_id, $task_id)
    {
        $data = $request->validate([
            'comment' => 'required|string|max:5000',
        ]);

        $comment = ProjectTaskComment::create([
            'project_task_id' => $task_id,
            'comment'         => $data['comment'],
            'created_by'      => $request->user()->id,
        ]);

        return new ProjectTaskCommentResource($comment);
    }

    // GET /projects/{project_id}/tasks/{task_id}/comments/{comment}
    public function show($project_id, $task_id, ProjectTaskComment $comment)
    {
        abort_if($comment->project_task_id != $task_id, 404);
        return new ProjectTaskCommentResource($comment);
    }

    // PUT/PATCH /projects/{project_id}/tasks/{task_id}/comments/{comment}
    public function update(Request $request, $project_id, $task_id, ProjectTaskComment $comment)
    {
        abort_if($comment->project_task_id != $task_id, 404);

        $data = $request->validate([
            'comment' => 'required|string|max:5000',
        ]);

        $comment->update(['comment' => $data['comment']]);

        return new ProjectTaskCommentResource($comment);
    }

    // DELETE /projects/{project_id}/tasks/{task_id}/comments/{comment}
    public function destroy($project_id, $task_id, ProjectTaskComment $comment)
    {
        abort_if($comment->project_task_id != $task_id, 404);
        $comment->delete();

        return response()->json(null, 204);
    }
}