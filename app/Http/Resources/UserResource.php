<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'photo' =>  $this->photo == NULL ? null : (Storage::disk('public')->exists($this->photo) ? Storage::disk('public')->url($this->photo) : null),
        ];
    }
}
