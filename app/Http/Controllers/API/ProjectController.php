<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectWithTasksResource;

class ProjectController extends BaseController
{
    public function index()
    {
        $projects = Project::all();
        return  $this->sendResponse(
            ProjectResource::collection($projects),
            'Projects retrieved successfully.'
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'user_id' => $request->user()->id,
            'budget' => $request->input('budget', 0), // Valor por defecto 0 si no se proporciona
            'start_date' => now(),
            'end_date' => $request->input('end_date', null), // Valor por defecto null si no se proporciona
            'client_name' => $request->input('client_name', null),
            'client_email' => $request->input('client_email', null),
            'client_phone' => $request->input('client_phone', null),
        ]);

        return $this->sendResponse(
            new ProjectResource($project),
            'Project created successfully.'
        );
    }

    public function show(int $id)
    {
        $project = Project::findOrFail($id);
        
        return $this->sendResponse(
            new ProjectResource($project),
            'Project retrieved successfully.'
        );
    }

    public function update(Request $request, int $id)
    {
        //$this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:projects,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $project = Project::findOrFail($id);
        $project->update($validated);

        return $this->sendResponse(
            new ProjectResource($project),
            'Project updated successfully.'
        );
    }

    public function destroy(int $id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('delete', $project);
        $project->delete();
        return response()->noContent();
    }
}
