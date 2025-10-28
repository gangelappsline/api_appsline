<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

Route::get("/", function () {
    return response()->json(['message' => 'Welcome to the API']);
});
Route::post('/register', [Controllers\API\AuthController::class, 'register']);
Route::post('/login', [Controllers\API\AuthController::class, 'login']);

Route::prefix('/public')->group(function () {
    Route::apiResource('/projects', Controllers\API\Public\ProjectController::class);
    Route::apiResource('/leads', Controllers\API\Public\LeadController::class);
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('/users', Controllers\API\UserController::class);
    Route::apiResource('/posts', Controllers\API\PostController::class);
    Route::apiResource('/dashboard', Controllers\API\DashboardController::class);

    Route::apiResource('/projects', Controllers\API\ProjectController::class);
    Route::post('/projects/{id}/logo', function(Request $request, $id){
       $validated = $request->validate([
           'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
       ]);

       $project = Project::findOrFail($id);
       if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = "projects/{$project->id}";
            if (!Storage::disk('public')->exists($path)) {
                Storage::disk('public')->makeDirectory($path);
            }
            $name = str_replace([" ", "/", "@"], '_', $file->getClientOriginalName());
            Storage::disk('public')->putFileAs($path, $file, $name);
            $project->logo = $path . '/' . $name;
            $project->save();
        }

       return response()->json(['message' => 'Logo uploaded successfully.']);
    });
    Route::apiResource('/projects/{project_id}/tasks', Controllers\API\ProjectTaskController::class);

    // Nuevo recurso de comentarios de tareas
    Route::apiResource('/projects/{project_id}/tasks/{task_id}/comments', Controllers\API\ProjectTaskCommentController::class);

    // Nuevo recurso de archivos de tareas
    Route::apiResource('/projects/{project_id}/tasks/{task_id}/files', Controllers\API\ProjectTaskFileController::class);

    Route::apiResource('/users', Controllers\API\UserController::class);
    Route::post('/logout', [Controllers\API\AuthController::class, 'logout']);
});
