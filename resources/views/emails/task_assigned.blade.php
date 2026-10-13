@extends('emails.layouts.master')

@section('content')

    @php
        // Filas del panel de detalle (solo se muestran las que tienen dato).
        $rows = [
            'Proyecto' => e($task->project?->name ?? '—'),
            'Estado' => e($task->statusLabel()),
            'Prioridad' => e($task->priorityLabel()),
        ];

        if ($task->due_date) {
            $rows['Fecha de entrega'] = e($task->due_date->translatedFormat('d M Y, H:i'))
                .' <span style="color:'.config('appsline.colors.muted').';">('
                .e($task->due_date->diffForHumans()).')</span>';
        } else {
            $rows['Fecha de entrega'] = 'Sin fecha definida';
        }

        if ($task->estimated_hours) {
            $rows['Horas estimadas'] = e($task->estimated_hours).' h';
        }
        if ($task->category) {
            $rows['Categoría'] = e($task->category);
        }
        if ($task->tags) {
            $rows['Etiquetas'] = e($task->tags);
        }
        if ($assignerName) {
            $rows['Asignada por'] = e($assignerName);
        }
        $rows['Creada el'] = e($task->created_at?->translatedFormat('d M Y, H:i') ?? '—');
    @endphp

    <span class="badge" style="display:inline-block; background-color:#EEF4FF; border:1px solid #C9DBFB; border-radius:999px; color:{{ config('appsline.colors.primary') }}; font-family:{{ config('appsline.font') }}; font-size:12px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; padding:6px 14px;">
        {{ $reassigned ?? false ? 'Tarea reasignada' : 'Nueva tarea asignada' }}
    </span>

    <div style="height:18px; line-height:18px; font-size:18px;">&nbsp;</div>

    <x-email.heading>{{ $task->title }}</x-email.heading>

    <p class="p" style="margin:0 0 16px 0; font-family:{{ config('appsline.font') }}; font-size:16px; line-height:26px; color:{{ config('appsline.colors.body') }};">
        Hola {{ $assigneeName ?? '—' }},
        {{ $reassigned ? 'la siguiente tarea ahora está asignada a ti' : 'se te ha asignado la siguiente tarea' }}@if ($task->project) en el proyecto <strong style="color:{{ config('appsline.colors.ink') }};">{{ $task->project->name }}</strong>@endif.
        Revisa la descripción completa y la fecha de entrega:
    </p>

    @if ($task->isOverdue())
        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#FEE2E2; border:1px solid #FECACA; border-radius:14px;">
            <tr>
                <td style="padding:14px 20px; font-family:{{ config('appsline.font') }}; font-size:14px; line-height:22px; color:#991B1B;">
                    <strong>Esta tarea ya venció</strong> (hace {{ $task->daysOverdue() }} día(s)). Por favor revísala cuanto antes.
                </td>
            </tr>
        </table>
        <div style="height:16px; line-height:16px; font-size:16px;">&nbsp;</div>
    @elseif ($task->isDueToday())
        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#FEF3C7; border:1px solid #FDE68A; border-radius:14px;">
            <tr>
                <td style="padding:14px 20px; font-family:{{ config('appsline.font') }}; font-size:14px; line-height:22px; color:#92400E;">
                    <strong>Esta tarea vence hoy.</strong> Tenlo en cuenta al organizar tu día.
                </td>
            </tr>
        </table>
        <div style="height:16px; line-height:16px; font-size:16px;">&nbsp;</div>
    @endif

    <x-email.heading level="h2">Descripción de la tarea</x-email.heading>

    @if (filled($task->description))
        <div style="font-family:{{ config('appsline.font') }}; font-size:16px; line-height:26px; color:{{ config('appsline.colors.body') }}; margin:0 0 8px 0;">
            {!! nl2br(e($task->description)) !!}
        </div>
    @else
        <p class="p" style="margin:0 0 16px 0; font-family:{{ config('appsline.font') }}; font-size:16px; line-height:26px; color:{{ config('appsline.colors.muted') }};">
            La tarea no tiene descripción. Si necesitas más detalle, pregúntale a quien te la asignó.
        </p>
    @endif

    <div style="height:16px; line-height:16px; font-size:16px;">&nbsp;</div>

    <x-email.panel title="Detalle de la tarea" :rows="$rows" />

    <div style="height:24px; line-height:24px; font-size:24px;">&nbsp;</div>

    <x-email.button href="{{ $taskUrl }}" label="Ver tarea" />

    <div style="height:14px; line-height:14px; font-size:14px;">&nbsp;</div>

    <p class="small" style="margin:0 0 12px 0; font-family:{{ config('appsline.font') }}; font-size:13px; line-height:21px; color:{{ config('appsline.colors.muted') }};">
        Si no esperabas esta asignación o necesitas ayuda, responde este correo o contacta al responsable del proyecto.
    </p>

    <div class="divider" style="height:1px; border-top:1px solid #EDF1F7; font-size:0; line-height:0; margin:28px 0;">&nbsp;</div>

    <x-email.signature />

@endsection
