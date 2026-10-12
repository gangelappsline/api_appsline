<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectTaskCommentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'task_id'  => $this->task_id,
            'comment'          => $this->comment,
            'created_by'       => [
                'id'    => $this->author->id,
                'name'  => $this->author->name,
                'photo' => $this->author->photo == NULL ? null : Storage::disk('public')->url($this->author->photo),
            ],
            'created_at'       => $this->created_at,
        ];
    }
}