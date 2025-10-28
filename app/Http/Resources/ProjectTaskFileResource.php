<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectTaskFileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'project_task_id' => $this->project_task_id,
            'file_name'       => $this->file_name,
            'file_type'       => $this->file_type,
            'file_size'      => $this->file_size,
            'file_path'      => $this->file_path,
            'url'             => $this->file_path ? Storage::disk('public')->url($this->file_path) : null,
            'user'         => $this->uploader,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
