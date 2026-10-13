@extends('emails.layouts.master')

@section('content')

    @php
        $period = $weekStart->translatedFormat('d M').' – '.$weekEnd->translatedFormat('d M Y');
        $summaryRows = $summaries->map(fn ($summary) => [
            e($summary['user']->name),
            (string) $summary['completed']->count(),
            (string) $summary['on_time'],
            (string) $summary['late'],
            (string) $summary['overdue']->count(),
            (string) $summary['open'],
        ])->all();

        if ($summaries->isNotEmpty()) {
            $summaryRows[] = [
                '<strong>Total</strong>',
                '<strong>'.$totals['completed'].'</strong>',
                '<strong>'.$totals['on_time'].'</strong>',
                '<strong>'.$totals['late'].'</strong>',
                '<strong>'.$totals['overdue'].'</strong>',
                '<strong>'.$summaries->sum('open').'</strong>',
            ];
        }
    @endphp

    <span class="badge" style="display:inline-block; background-color:#EEF4FF; border:1px solid #C9DBFB; border-radius:999px; color:{{ config('appsline.colors.primary') }}; font-family:{{ config('appsline.font') }}; font-size:12px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; padding:6px 14px;">
        Reporte semanal · Equipo
    </span>

    <div style="height:18px; line-height:18px; font-size:18px;">&nbsp;</div>

    <x-email.heading>Resumen semanal de tareas</x-email.heading>

    <p class="p" style="margin:0 0 16px 0; font-family:{{ config('appsline.font') }}; font-size:16px; line-height:26px; color:{{ config('appsline.colors.body') }};">
        Semana del <strong style="color:{{ config('appsline.colors.ink') }};">{{ $period }}</strong>.
        En total se completaron <strong style="color:{{ config('appsline.colors.ink') }};">{{ $totals['completed'] }} tarea(s)</strong>
        ({{ $totals['on_time'] }} a tiempo, {{ $totals['late'] }} tarde)
        y quedan <strong style="color:#B91C1C;">{{ $totals['overdue'] }} vencida(s)</strong> sin entregar
        entre {{ $totals['users'] }} usuario(s).
    </p>

    <x-email.heading level="h2">Resumen por usuario</x-email.heading>

    @if ($summaries->isEmpty())
        <p class="p" style="margin:0 0 16px 0; font-family:{{ config('appsline.font') }}; font-size:15px; line-height:24px; color:{{ config('appsline.colors.muted') }};">
            Esta semana no hubo tareas completadas ni vencidas.
        </p>
    @else
        <x-email.data-table
            :head="['Usuario', 'Completadas', 'A tiempo', 'Tarde', 'Vencidas', 'Abiertas']"
            :rows="$summaryRows"
            :footer="[['Total', (string) $totals['completed'], (string) $totals['on_time'], (string) $totals['late'], (string) $totals['overdue'], '']]"
        />
    @endif

    <div style="height:28px; line-height:28px; font-size:28px;">&nbsp;</div>

    <x-email.heading level="h2">Detalle por usuario</x-email.heading>

    @forelse ($summaries as $summary)
        <div style="font-family:{{ config('appsline.font') }}; font-size:16px; line-height:24px; font-weight:700; color:{{ config('appsline.colors.ink') }}; margin:0 0 4px 0;">
            {{ $summary['user']->name }}
        </div>
        <div style="font-family:{{ config('appsline.font') }}; font-size:13px; line-height:20px; color:{{ config('appsline.colors.muted') }}; margin:0 0 10px 0;">
            {{ $summary['completed']->count() }} completada(s) · {{ $summary['on_time'] }} a tiempo · {{ $summary['late'] }} tarde · {{ $summary['overdue']->count() }} vencida(s) · {{ $summary['open'] }} abierta(s) en total
        </div>

        @if ($summary['completed']->isNotEmpty())
            <div style="font-family:{{ config('appsline.font') }}; font-size:12px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:#047857; margin:0 0 6px 0;">Completadas</div>
            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                @foreach ($summary['completed'] as $task)
                    <tr>
                        <td style="padding:7px 10px 7px 0; border-bottom:1px solid #EDF1F7; font-family:{{ config('appsline.font') }}; font-size:14px; line-height:21px; color:{{ config('appsline.colors.body') }};">
                            <a href="{{ $task->url() }}" target="_blank" style="font-weight:600; color:{{ config('appsline.colors.ink') }}; text-decoration:none;">{{ $task->title }}</a>
                            <span style="color:{{ config('appsline.colors.muted') }};">· {{ $task->project?->name ?? 'Sin proyecto' }}</span>
                        </td>
                        <td align="right" style="padding:7px 0; border-bottom:1px solid #EDF1F7; font-family:{{ config('appsline.font') }}; font-size:13px; line-height:20px; white-space:nowrap; color:{{ $task->wasCompletedLate() ? '#B45309' : '#047857' }}; font-weight:600;">
                            {{ $task->wasCompletedLate() ? 'Tarde' : 'A tiempo' }} · {{ $task->completed_at?->translatedFormat('d M') }}
                        </td>
                    </tr>
                @endforeach
            </table>
            <div style="height:12px; line-height:12px; font-size:12px;">&nbsp;</div>
        @endif

        @if ($summary['overdue']->isNotEmpty())
            <div style="font-family:{{ config('appsline.font') }}; font-size:12px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:#B91C1C; margin:0 0 6px 0;">Vencidas sin entregar</div>
            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                @foreach ($summary['overdue'] as $task)
                    <tr>
                        <td style="padding:7px 10px 7px 0; border-bottom:1px solid #EDF1F7; font-family:{{ config('appsline.font') }}; font-size:14px; line-height:21px; color:{{ config('appsline.colors.body') }};">
                            <a href="{{ $task->url() }}" target="_blank" style="font-weight:600; color:{{ config('appsline.colors.ink') }}; text-decoration:none;">{{ $task->title }}</a>
                            <span style="color:{{ config('appsline.colors.muted') }};">· {{ $task->project?->name ?? 'Sin proyecto' }}</span>
                        </td>
                        <td align="right" style="padding:7px 0; border-bottom:1px solid #EDF1F7; font-family:{{ config('appsline.font') }}; font-size:13px; line-height:20px; white-space:nowrap; color:#B91C1C; font-weight:600;">
                            Venció el {{ $task->due_date?->translatedFormat('d M') }} · hace {{ $task->daysOverdue() }} día(s)
                        </td>
                    </tr>
                @endforeach
            </table>
            <div style="height:12px; line-height:12px; font-size:12px;">&nbsp;</div>
        @endif

        @if (! $loop->last)
            <div class="divider" style="height:1px; border-top:1px solid #EDF1F7; font-size:0; line-height:0; margin:18px 0;">&nbsp;</div>
        @endif
    @empty
        <p class="p" style="margin:0 0 16px 0; font-family:{{ config('appsline.font') }}; font-size:15px; line-height:24px; color:{{ config('appsline.colors.muted') }};">
            Sin actividad que reportar esta semana.
        </p>
    @endforelse

    <div style="height:16px; line-height:16px; font-size:16px;">&nbsp;</div>

    <x-email.button href="{{ $projectsUrl }}" label="Abrir el panel de proyectos" />

    <div style="height:14px; line-height:14px; font-size:14px;">&nbsp;</div>

    <p class="small" style="margin:0 0 12px 0; font-family:{{ config('appsline.font') }}; font-size:13px; line-height:21px; color:{{ config('appsline.colors.muted') }};">
        Reporte automático de los viernes. Solo incluye a usuarios con tareas completadas en la semana o tareas vencidas.
    </p>

    <div class="divider" style="height:1px; border-top:1px solid #EDF1F7; font-size:0; line-height:0; margin:28px 0;">&nbsp;</div>

    <x-email.signature />

@endsection
