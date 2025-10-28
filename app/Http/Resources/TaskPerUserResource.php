<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class TaskPerUserResource extends JsonResource
{
    public function toArray($request)
    {
        // soporta tanto stdClass/array provenientes de consultas RAW como modelos
        $userId = $this->id ?? ($this['id'] ?? null);
        $name   = $this->name ?? ($this['name'] ?? null);
        $total  = (int) ($this->total_completed ?? ($this['total_completed'] ?? 0));
        $onTime = (int) ($this->on_time ?? ($this['on_time'] ?? 0));
        $late   = (int) ($this->late ?? ($this['late'] ?? 0));

        // intentar obtener foto desde la tabla users (si no fue incluida en la consulta)
        $photoUrl = null;
        if ($userId) {
            $user = User::select('id','name','photo')->find($userId);
            if ($user && !empty($user->photo)) {
                $photoUrl = asset('storage/' . ltrim($user->photo, '/'));
            }
            // si name no viene en la fila, usar el del usuario
            if (empty($name) && $user) {
                $name = $user->name;
            }
        }

        $onTimeRate = $total > 0 ? round(($onTime / $total) * 100, 2) : null;

        return [
            'id' => $userId,
            'name' => $name,
            'photo' => $photoUrl,
            'total_completed' => $total,
            'on_time' => $onTime,
            'late' => $late,
            'on_time_rate' => $onTimeRate, // porcentaje
        ];
    }
}