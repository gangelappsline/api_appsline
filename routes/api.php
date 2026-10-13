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
    // Nombres propios (public.*) para no chocar con los recursos autenticados de abajo:
    // dos rutas con el mismo nombre rompen `php artisan route:cache` y `php artisan optimize`
    // (LogicException: Another route has already been assigned name [projects.index]).
    Route::apiResource('/projects', Controllers\API\Public\ProjectController::class)
        ->names('public.projects');
    Route::apiResource('/leads', Controllers\API\Public\LeadController::class)
        ->names('public.leads');
});

Route::middleware('auth:api')->group(function () {
    Route::post('/users/{id}/avatar', [Controllers\API\UserController::class, 'updateAvatar']);
    Route::apiResource('/users', Controllers\API\UserController::class);
    Route::apiResource('/posts', Controllers\API\PostController::class);
    Route::apiResource('/dashboard', Controllers\API\DashboardController::class);

    Route::apiResource('/projects', Controllers\API\ProjectController::class);
    Route::post('/projects/{id}/logo', function(Request $request, $id){
       $validated = $request->validate([
           'logo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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
    Route::apiResource('/projects/{project_id}/tasks/{task_id}/subtasks', Controllers\API\ProjectTaskSubtaskController::class);

    // Nuevo recurso de archivos de tareas
    Route::apiResource('/projects/{project_id}/tasks/{task_id}/files', Controllers\API\ProjectTaskFileController::class);
    Route::get('clients/options', [Controllers\API\ClientController::class, 'options']);
    Route::patch('clients/{client}/status', [Controllers\API\ClientController::class, 'updateStatus']);
    Route::post('clients/{id}/restore', [Controllers\API\ClientController::class, 'restore']);

    Route::apiResource('clients', Controllers\API\ClientController::class);

    // `/users` ya está declarado arriba; declararlo dos veces duplica los nombres de ruta.
    Route::post('/logout', [Controllers\API\AuthController::class, 'logout']);
});
