<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectTaskFileResource;
use App\Models\ProjectTaskFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectTaskFileController extends BaseController
{
    // GET /projects/{project_id}/tasks/{task_id}/files
    public function index($project_id, $task_id)
    {
        $files = ProjectTaskFile::where('project_task_id', $task_id)
            ->latest()
            ->get();

        return ProjectTaskFileResource::collection($files);
    }

    // POST /projects/{project_id}/tasks/{task_id}/files
    public function store(Request $request, $project_id, $task_id)
    {
        // Accept multiple files via input name "files[]" or single file via "file"
        $validated = $request->validate([
            'files'   => 'required_without:file|array',
            'files.*' => 'file|max:20480', // 20MB each
            'file'    => 'required_without:files|file|max:20480',
        ]);

        $uploads = [];

        if ($request->hasFile('files')) {
            $uploads = $request->file('files');
        } elseif ($request->hasFile('file')) {
            $uploads = [$request->file('file')];
        }

        $created = [];

        foreach ($uploads as $uploaded) {
            $dir = "projects/{$project_id}/tasks/{$task_id}/files";
            $base = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
            $ext  = $uploaded->getClientOriginalExtension();
            $name = (Str::slug($base) ?: 'file') . '-' . uniqid() . '.' . $ext;

            Storage::disk('public')->putFileAs($dir, $uploaded, $name);

            $file = ProjectTaskFile::create([
                'project_task_id' => $task_id,
                'file_name'       => $name,
                'file_path'       => "{$dir}/{$name}",
                'file_size'       => $uploaded->getSize(),
                'file_type'       => $uploaded->getClientMimeType(),
                'user_id'         => $request->user()->id,
            ]);

            $created[] = $file;
        }

        return ProjectTaskFileResource::collection(collect($created));
    }

    // GET /projects/{project_id}/tasks/{task_id}/files/{file}
    public function show($project_id, $task_id, $id)
    {
        $file = ProjectTaskFile::findOrFail($id);
        return new ProjectTaskFileResource($file);
    }

    // DELETE /projects/{project_id}/tasks/{task_id}/files/{file}
    public function destroy($project_id, $task_id, $id)
    {
        $file = ProjectTaskFile::findOrFail($id);
        abort_if($file->project_task_id != $task_id, 404);

        if ($file->file_path) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return response()->json(null, 204);
    }
}