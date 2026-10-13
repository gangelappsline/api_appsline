@extends('emails.layouts.master')

@section('content')

    @php
        [$badgeBg, $badgeBorder, $badgeColor] = $type->badgeColors();
        $firstName = str($recipient->name)->before(' ');
        $overdueCount = $tasks->filter(fn ($task) => $task->isOverdue())->count();
        $dueTodayCount = $tasks->filter(fn ($task) => $task->isDueToday())->count();
    @endphp

    <span class="badge" style="display:inline-block; background-color:{{ $badgeBg }}; border:1px solid {{ $badgeBorder }}; border-radius:999px; color:{{ $badgeColor }}; font-family:{{ config('appsline.font') }}; font-size:12px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; padding:6px 14px;">
        {{ $type->label() }}
    </span>

    <div style="height:18px; line-height:18px; font-size:18px;">&nbsp;</div>

    <x-email.heading>{{ $type->heading($firstName, $count) }}</x-email.heading>

    <p class="p" style="margin:0 0 16px 0; font-family:{{ config('appsline.font') }}; font-size:16px; line-height:26px; color:{{ config('appsline.colors.body') }};">
        {{ $type->intro() }}
    </p>

    @if ($type->value === 'pending' && ($overdueCount > 0 || $dueTodayCount > 0))
        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#FEF3C7; border:1px solid #FDE68A; border-radius:14px;">
            <tr>
                <td style="padding:14px 20px; font-family:{{ config('appsline.font') }}; font-size:14px; line-height:22px; color:#92400E;">
                    De estas tareas,
                    @if ($overdueCount > 0)<strong>{{ $overdueCount }} ya venció/vencieron</strong>@endif
                    @if ($overdueCount > 0 && $dueTodayCount > 0) y @endif
                    @if ($dueTodayCount > 0)<strong>{{ $dueTodayCount }} vence(n) hoy</strong>@endif.
                    Empieza por ellas.
                </td>
            </tr>
        </table>
        <div style="height:16px; line-height:16px; font-size:16px;">&nbsp;</div>
    @endif

    <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <th style="font-family:{{ config('appsline.font') }}; font-size:12px; letter-spacing:.8px; text-transform:uppercase; color:{{ config('appsline.colors.muted') }}; font-weight:700; text-align:left; padding:0 0 10px 0; border-bottom:1px solid {{ config('appsline.colors.border') }};">
                Tarea
            </th>
            <th style="font-family:{{ config('appsline.font') }}; font-size:12px; letter-spacing:.8px; text-transform:uppercase; color:{{ config('appsline.colors.muted') }}; font-weight:700; text-align:right; padding:0 0 10px 0; border-bottom:1px solid {{ config('appsline.colors.border') }};">
                Entrega
            </th>
        </tr>

        @foreach ($tasks as $task)
            @php
                $dueColor = config('appsline.colors.body');
                if (! $task->due_date) {
                    $dueText = 'Sin fecha';
                } elseif ($task->isOverdue()) {
                    $dueText = 'Venció el '.$task->due_date->translatedFormat('d M Y').' · hace '.$task->daysOverdue().' día(s)';
                    $dueColor = '#B91C1C';
                } elseif ($task->isDueToday()) {
                    $dueText = 'Vence hoy · '.$task->due_date->translatedFormat('H:i');
                    $dueColor = '#92400E';
                } else {
                    $dueText = 'Vence el '.$task->due_date->translatedFormat('d M Y').' · '.$task->due_date->diffForHumans();
                }

                $priorityColor = match (strtolower((string) ($task->priority ?: 'medium'))) {
                    'high', 'alta', 'urgent', 'urgente' => '#B91C1C',
                    'low', 'baja' => '#8A94A6',
                    default => '#0B47C4',
                };
            @endphp
            <tr>
                <td style="padding:13px 12px 13px 0; border-bottom:1px solid #EDF1F7; vertical-align:top;">
                    <a href="{{ $task->url() }}" target="_blank" style="font-family:{{ config('appsline.font') }}; font-size:15px; line-height:22px; font-weight:700; color:{{ config('appsline.colors.ink') }}; text-decoration:none;">
                        {{ $task->title }}
                    </a>
                    <div style="font-family:{{ config('appsline.font') }}; font-size:13px; line-height:20px; color:{{ config('appsline.colors.muted') }};">
                        {{ $task->project?->name ?? 'Sin proyecto' }}
                        · {{ $task->statusLabel() }}
                        · <span style="color:{{ $priorityColor }}; font-weight:600;">Prioridad {{ $task->priorityLabel() }}</span>
                    </div>
                </td>
                <td align="right" style="padding:13px 0; border-bottom:1px solid #EDF1F7; vertical-align:top; font-family:{{ config('appsline.font') }}; font-size:13px; line-height:20px; font-weight:600; color:{{ $dueColor }}; white-space:nowrap;">
                    {{ $dueText }}
                </td>
            </tr>
        @endforeach
    </table>

    <div style="height:24px; line-height:24px; font-size:24px;">&nbsp;</div>

    <x-email.button href="{{ $projectsUrl }}" label="Ver mis proyectos" />

    <div style="height:14px; line-height:14px; font-size:14px;">&nbsp;</div>

    <p class="small" style="margin:0 0 12px 0; font-family:{{ config('appsline.font') }}; font-size:13px; line-height:21px; color:{{ config('appsline.colors.muted') }};">
        Recibes este aviso porque tienes tareas asignadas en {{ config('appsline.name') }}. Si alguna tarea ya no aplica, coméntalo en la propia tarea.
    </p>

    <div class="divider" style="height:1px; border-top:1px solid #EDF1F7; font-size:0; line-height:0; margin:28px 0;">&nbsp;</div>

    <x-email.signature />

@endsection
