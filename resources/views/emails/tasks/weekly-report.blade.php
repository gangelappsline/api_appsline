@extends('emails.layouts.master')

@section('content')

    @php
        $firstName = str($recipient->name)->before(' ');
        $period = $weekStart->translatedFormat('d M').' – '.$weekEnd->translatedFormat('d M Y');
    @endphp

    <span class="badge" style="display:inline-block; background-color:#EEF4FF; border:1px solid #C9DBFB; border-radius:999px; color:{{ config('appsline.colors.primary') }}; font-family:{{ config('appsline.font') }}; font-size:12px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; padding:6px 14px;">
        Resumen semanal
    </span>

    <div style="height:18px; line-height:18px; font-size:18px;">&nbsp;</div>

    <x-email.heading>Tu semana, {{ $firstName }}</x-email.heading>

    <p class="p" style="margin:0 0 16px 0; font-family:{{ config('appsline.font') }}; font-size:16px; line-height:26px; color:{{ config('appsline.colors.body') }};">
        Así quedó tu trabajo del <strong style="color:{{ config('appsline.colors.ink') }};">{{ $period }}</strong>:
        completaste {{ $completed->count() }} tarea(s)
        @if ($overdue->isNotEmpty())
            y todavía tienes {{ $overdue->count() }} vencida(s) que necesitan atención.
        @else
            y no tienes ninguna tarea vencida.
        @endif
    </p>

    {{-- Cifras de la semana --}}
    <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            @foreach ([
                ['valor' => $completed->count(), 'etiqueta' => 'Completadas', 'color' => config('appsline.colors.ink')],
                ['valor' => $stats['on_time'], 'etiqueta' => 'A tiempo', 'color' => '#047857'],
                ['valor' => $stats['late'], 'etiqueta' => 'Tarde', 'color' => '#B45309'],
                ['valor' => $overdue->count(), 'etiqueta' => 'Vencidas', 'color' => '#B91C1C'],
            ] as $stat)
                <td class="stack" width="25%" style="padding:0 4px;">
                    <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" class="stat" style="background-color:{{ config('appsline.colors.bg') }}; border-radius:14px;">
                        <tr>
                            <td align="center" style="padding:16px 6px;">
                                <div style="font-family:{{ config('appsline.font') }}; font-size:26px; line-height:30px; font-weight:700; color:{{ $stat['color'] }};">{{ $stat['valor'] }}</div>
                                <div style="font-family:{{ config('appsline.font') }}; font-size:12px; line-height:18px; letter-spacing:.5px; text-transform:uppercase; color:{{ config('appsline.colors.muted') }};">{{ $stat['etiqueta'] }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            @endforeach
        </tr>
    </table>

    <div style="height:28px; line-height:28px; font-size:28px;">&nbsp;</div>

    <x-email.heading level="h2">Completadas esta semana ({{ $completed->count() }})</x-email.heading>

    @if ($completed->isEmpty())
        <p class="p" style="margin:0 0 16px 0; font-family:{{ config('appsline.font') }}; font-size:15px; line-height:24px; color:{{ config('appsline.colors.muted') }};">
            No se registraron tareas completadas esta semana.
        </p>
    @else
        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <th style="font-family:{{ config('appsline.font') }}; font-size:12px; letter-spacing:.8px; text-transform:uppercase; color:{{ config('appsline.colors.muted') }}; font-weight:700; text-align:left; padding:0 0 10px 0; border-bottom:1px solid {{ config('appsline.colors.border') }};">Tarea</th>
                <th style="font-family:{{ config('appsline.font') }}; font-size:12px; letter-spacing:.8px; text-transform:uppercase; color:{{ config('appsline.colors.muted') }}; font-weight:700; text-align:right; padding:0 0 10px 0; border-bottom:1px solid {{ config('appsline.colors.border') }};">Entrega</th>
            </tr>
            @foreach ($completed as $task)
                <tr>
                    <td style="padding:12px 12px 12px 0; border-bottom:1px solid #EDF1F7; vertical-align:top;">
                        <a href="{{ $task->url() }}" target="_blank" style="font-family:{{ config('appsline.font') }}; font-size:15px; line-height:22px; font-weight:700; color:{{ config('appsline.colors.ink') }}; text-decoration:none;">{{ $task->title }}</a>
                        <div style="font-family:{{ config('appsline.font') }}; font-size:13px; line-height:20px; color:{{ config('appsline.colors.muted') }};">
                            {{ $task->project?->name ?? 'Sin proyecto' }}
                            @if ($task->wasCompletedLate())
                                · <span style="color:#B45309; font-weight:600;">Tarde@if ($task->daysCompletedLate() > 0) ({{ $task->daysCompletedLate() }} día(s))@endif</span>
                            @else
                                · <span style="color:#047857; font-weight:600;">A tiempo</span>
                            @endif
                        </div>
                    </td>
                    <td align="right" style="padding:12px 0; border-bottom:1px solid #EDF1F7; vertical-align:top; font-family:{{ config('appsline.font') }}; font-size:13px; line-height:20px; font-weight:600; color:{{ config('appsline.colors.body') }}; white-space:nowrap;">
                        {{ $task->completed_at?->translatedFormat('d M, H:i') }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    <div style="height:28px; line-height:28px; font-size:28px;">&nbsp;</div>

    <x-email.heading level="h2">Vencidas sin entregar ({{ $overdue->count() }})</x-email.heading>

    @if ($overdue->isEmpty())
        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#ECFDF5; border:1px solid #A7F3D0; border-radius:14px;">
            <tr>
                <td style="padding:14px 20px; font-family:{{ config('appsline.font') }}; font-size:14px; line-height:22px; color:#065F46;">
                    No tienes tareas vencidas. Buen trabajo, así se cierra una semana.
                </td>
            </tr>
        </table>
    @else
        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <th style="font-family:{{ config('appsline.font') }}; font-size:12px; letter-spacing:.8px; text-transform:uppercase; color:{{ config('appsline.colors.muted') }}; font-weight:700; text-align:left; padding:0 0 10px 0; border-bottom:1px solid {{ config('appsline.colors.border') }};">Tarea</th>
                <th style="font-family:{{ config('appsline.font') }}; font-size:12px; letter-spacing:.8px; text-transform:uppercase; color:{{ config('appsline.colors.muted') }}; font-weight:700; text-align:right; padding:0 0 10px 0; border-bottom:1px solid {{ config('appsline.colors.border') }};">Vencimiento</th>
            </tr>
            @foreach ($overdue as $task)
                <tr>
                    <td style="padding:12px 12px 12px 0; border-bottom:1px solid #EDF1F7; vertical-align:top;">
                        <a href="{{ $task->url() }}" target="_blank" style="font-family:{{ config('appsline.font') }}; font-size:15px; line-height:22px; font-weight:700; color:{{ config('appsline.colors.ink') }}; text-decoration:none;">{{ $task->title }}</a>
                        <div style="font-family:{{ config('appsline.font') }}; font-size:13px; line-height:20px; color:{{ config('appsline.colors.muted') }};">
                            {{ $task->project?->name ?? 'Sin proyecto' }} · {{ $task->statusLabel() }}
                        </div>
                    </td>
                    <td align="right" style="padding:12px 0; border-bottom:1px solid #EDF1F7; vertical-align:top; font-family:{{ config('appsline.font') }}; font-size:13px; line-height:20px; font-weight:600; color:#B91C1C; white-space:nowrap;">
                        {{ $task->due_date?->translatedFormat('d M Y') }}<br>
                        hace {{ $task->daysOverdue() }} día(s)
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    <div style="height:24px; line-height:24px; font-size:24px;">&nbsp;</div>

    <x-email.button href="{{ $projectsUrl }}" label="Ver mis proyectos" />

    <div class="divider" style="height:1px; border-top:1px solid #EDF1F7; font-size:0; line-height:0; margin:28px 0;">&nbsp;</div>

    <x-email.signature />

@endsection
