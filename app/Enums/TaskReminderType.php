<?php

namespace App\Enums;

/**
 * Tipos de recordatorio agrupado (digest) que se envían por cron:
 * pendientes en general, las que vencen hoy y las vencidas.
 */
enum TaskReminderType: string
{
    case Pending = 'pending';
    case DueToday = 'due_today';
    case Overdue = 'overdue';

    /** Etiqueta corta (badge del correo). */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Tareas pendientes',
            self::DueToday => 'Vencen hoy',
            self::Overdue => 'Tareas vencidas',
        };
    }

    /** Asunto del correo según el número de tareas. */
    public function subject(int $count): string
    {
        $tarea = $count === 1 ? 'tarea' : 'tareas';

        return match ($this) {
            self::Pending => "Tienes {$count} {$tarea} pendiente(s) por entregar",
            self::DueToday => "Hoy vencen {$count} {$tarea} asignada(s) a ti",
            self::Overdue => "Tienes {$count} {$tarea} vencida(s) sin entregar",
        };
    }

    /** Texto que se previsualiza en la bandeja de entrada. */
    public function preheader(int $count): string
    {
        return match ($this) {
            self::Pending => "Resumen de tus {$count} tarea(s) abiertas: revísalas y prioriza tu día.",
            self::DueToday => 'Estas tareas deben entregarse hoy. Échales un vistazo cuanto antes.',
            self::Overdue => 'Estas tareas ya pasaron su fecha de entrega y siguen sin entregarse.',
        };
    }

    /** Encabezado principal del cuerpo del correo. */
    public function heading(string $name, int $count): string
    {
        $tarea = $count === 1 ? 'tarea' : 'tareas';

        return match ($this) {
            self::Pending => "Hola {$name}, tienes {$count} {$tarea} pendiente(s)",
            self::DueToday => "Hola {$name}, hoy vencen {$count} {$tarea}",
            self::Overdue => "Hola {$name}, tienes {$count} {$tarea} vencida(s)",
        };
    }

    /** Párrafo introductorio del correo. */
    public function intro(): string
    {
        return match ($this) {
            self::Pending => 'Este es el resumen de las tareas que tienes abiertas (pendientes o en progreso). Entra a cada una para ver su descripción completa y actualizar su avance.',
            self::DueToday => 'Las siguientes tareas tienen fecha de entrega para el día de hoy. Si ya las terminaste, márcalas como completadas para que no se generen más avisos.',
            self::Overdue => 'Las siguientes tareas ya pasaron su fecha de entrega y todavía no se marcan como completadas. Si hay algún bloqueo, coméntalo en la tarea o avisa al responsable del proyecto.',
        };
    }

    /** Colores del badge: [fondo, borde, texto]. */
    public function badgeColors(): array
    {
        return match ($this) {
            self::Pending => ['#EEF4FF', '#C9DBFB', '#0B47C4'],
            self::DueToday => ['#FEF3C7', '#FDE68A', '#92400E'],
            self::Overdue => ['#FEE2E2', '#FECACA', '#991B1B'],
        };
    }
}
