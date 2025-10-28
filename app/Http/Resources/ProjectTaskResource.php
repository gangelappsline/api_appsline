<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectTaskResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,
            'status' => $this->status,
            'priority' => $this->priority,
            'category' => $this->category,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'photo' => $this->user->photo == NULL ? null : (Storage::disk('public')->exists($this->user->photo) ? Storage::disk('public')->url($this->user->photo) : null),
            ],
            'assigned_to' => $this->assignedTo ? [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
                'photo' => $this->assignedTo->photo == NULL ? null : (Storage::disk('public')->exists($this->assignedTo->photo) ? Storage::disk('public')->url($this->assignedTo->photo) : null),
            ] : null,
            'tags'=> $this->tags,
            'estimated_hours' => $this->estimated_hours,
            'due_date' => $this->due_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
