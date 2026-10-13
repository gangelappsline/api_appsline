<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProjectTask extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    /** Estados en los que la tarea todavía no se entrega. */
    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_IN_PROGRESS];

    protected $fillable = [
        'due_date',
        'status',
        'project_id',
        'task_id',
        'title',
        'description',
        'assigned_to',
        'estimated_hours',
        'actual_hours',
        'priority',
        'category',
        'tags',
        'completed_at',
        'created_by'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
        ];
    }

    // ─── Relaciones ──────────────────────────────────────────────────────

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /** Usuario que creó la tarea (relación histórica, no renombrar). */
    public function user()
    {
        return $this->belongsTo(User::class, "created_by", "id");
    }

    /** Alias legible de user(): quien creó la tarea. */
    public function creator()
    {
        return $this->belongsTo(User::class, "created_by", "id");
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class,  "assigned_to", "id");
    }

    public function subtasks()
    {
        return $this->hasMany(ProjectTask::class, 'task_id');
    }

    public function comments()
    {
        return $this->hasMany(ProjectTaskComment::class,'task_id')->latest();
    }

    public function files()
    {
        return $this->hasMany(ProjectTaskFile::class)->latest();
    }

    // ─── Scopes (reutilizados por correos, comandos de cron y dashboard) ─

    /** Tareas todavía no entregadas: pending + in_progress. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /** Tareas con responsable asignado. */
    public function scopeAssigned(Builder $query): Builder
    {
        return $query->whereNotNull('assigned_to');
    }

    /**
     * Tareas abiertas cuya fecha de entrega es hoy.
     * Es el bucket de "tienen que entregar el día actual".
     */
    public function scopeDueToday(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('due_date')
            ->whereDate('due_date', today());
    }

    /**
     * Tareas abiertas no entregadas cuya fecha de entrega ya pasó
     * (venció antes de hoy; las de hoy se notifican como "vencen hoy").
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay());
    }

    /** Tareas completadas entre dos fechas (para el reporte semanal). */
    public function scopeCompletedBetween(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->completed()
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$from, $to]);
    }

    // ─── Ayudantes de estado ─────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isOverdue(): bool
    {
        return ! $this->isCompleted()
            && $this->due_date !== null
            && $this->due_date->lt(now()->startOfDay());
    }

    public function isDueToday(): bool
    {
        return ! $this->isCompleted()
            && $this->due_date !== null
            && $this->due_date->isToday();
    }

    /** Días completos de retraso (0 si no está vencida). */
    public function daysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) $this->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay());
    }

    /** True si se completó después de su fecha de entrega. */
    public function wasCompletedLate(): bool
    {
        return $this->completed_at !== null
            && $this->due_date !== null
            && $this->completed_at->gt($this->due_date);
    }

    /** Días completos de retraso al completarla (0 si llegó a tiempo o el mismo día). */
    public function daysCompletedLate(): int
    {
        if (! $this->wasCompletedLate()) {
            return 0;
        }

        return (int) $this->due_date->copy()->startOfDay()->diffInDays($this->completed_at->copy()->startOfDay());
    }

    // ─── Presentación ────────────────────────────────────────────────────

    /** Enlace directo a la tarea en el frontend (se usa en los correos). */
    public function url(): string
    {
        return rtrim((string) config('tasks.frontend_url'), '/')
            ."/projects/{$this->project_id}/tasks/{$this->id}";
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En progreso',
            self::STATUS_COMPLETED => 'Completada',
            default => Str::title(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function priorityLabel(): string
    {
        return match (strtolower((string) ($this->priority ?: 'medium'))) {
            'low', 'baja' => 'Baja',
            'medium', 'media' => 'Media',
            'high', 'alta' => 'Alta',
            'urgent', 'urgente' => 'Urgente',
            default => Str::title((string) $this->priority),
        };
    }
}
