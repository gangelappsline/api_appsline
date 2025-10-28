<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <style>
    :root {
      --primary: {{ $primaryColor ?? '#0ea5a4' }};
      --text: #0f172a;
      --muted: #64748b;
      --bg: #f8fafc;
      --card: #ffffff;
    }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial; color:var(--text); margin:0; padding:20px; background:var(--bg); }
    .container { max-width:720px; margin:0 auto; }
    .card { background:var(--card); border-radius:8px; overflow:hidden; box-shadow:0 6px 18px rgba(15,23,42,0.06); }
    .header { padding:18px 24px; display:flex; align-items:center; gap:16px; border-bottom:1px solid rgba(15,23,42,0.04); }
    .logo { max-height:56px; object-fit:contain; }
    .title { font-size:18px; font-weight:700; color:var(--text); }
    .content { padding:24px; }
    h1 { margin:0 0 12px; font-size:20px; color:var(--text); }
    p { margin:0 0 12px; color:var(--muted); line-height:1.5; }
    .details { width:100%; border-collapse:collapse; margin:14px 0; }
    .details td { padding:10px; border:1px solid #eef2f6; color:var(--text); vertical-align:top; }
    .label { width:180px; font-weight:700; color:var(--muted); background:#fbfdff; }
    .cta { text-align:center; margin:20px 0; }
    .button { display:inline-block; background:var(--primary); color:#fff; padding:12px 20px; border-radius:8px; text-decoration:none; font-weight:600; }
    .meta { font-size:13px; color:var(--muted); margin-top:8px; }
    .footer { padding:16px 24px; text-align:center; font-size:13px; color:var(--muted); background:#fbfdff; }
    @media (max-width:520px) { .content { padding:16px; } .label { display:block; width:100%; } .button { width:100%; display:block; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="card" role="article" aria-label="Nueva tarea asignada">
      <div class="header">
        @if(!empty($projectLogo))
          <img src="{{ $projectLogo }}" alt="{{ $projectName }} logo" class="logo">
        @else
          <div style="width:56px;height:56px;border-radius:8px;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">
            {{ strtoupper(substr($projectName,0,1) ?: 'P') }}
          </div>
        @endif
        <div>
          <div class="title">Tarea asignada en {{ $projectName }}</div>
          <div class="meta">Notificación generada automáticamente</div>
        </div>
      </div>

      <div class="content">
        <h1>{{ $task->title }}</h1>

        <p>Hola {{ $assigneeName ?? 'Usuario' }},</p>

        <p>Se te ha asignado la siguiente tarea. Encontrarás abajo los detalles principales y un acceso directo para revisarla.</p>

        <table class="details" role="table" aria-label="Detalles de la tarea">
          <tr>
            <td class="label"><strong>Título</strong></td>
            <td>{{ $task->title }}</td>
          </tr>
          <tr>
            <td class="label"><strong>Descripción</strong></td>
            <td>{!! nl2br(e($task->description ?? '—')) !!}</td>
          </tr>
          <tr>
            <td class="label"><strong>Estado</strong></td>
            <td>{{ $task->status }}</td>
          </tr>
          <tr>
            <td class="label"><strong>Vencimiento</strong></td>
            <td>{{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->toDayDateTimeString() : '—' }}</td>
          </tr>
          <tr>
            <td class="label"><strong>Prioridad</strong></td>
            <td>{{ $task->priority ?? '—' }}</td>
          </tr>
          <tr>
            <td class="label"><strong>Horas estimadas</strong></td>
            <td>{{ $task->estimated_hours ?? '—' }}</td>
          </tr>
          <tr>
            <td class="label"><strong>Asignado por</strong></td>
            <td>{{ $assignerName ?? '—' }}</td>
          </tr>
        </table>

        <div class="cta">
          <a href="{{ $taskUrl }}" class="button" target="_blank" rel="noopener">Ver tarea</a>
        </div>

        <p class="meta">Si no esperabas esta asignación o necesitas ayuda, responde este correo o contacta al responsable del proyecto.</p>
      </div>

      <div class="footer">
        {{ $companyName }} · {{ $companyContact ?? '' }}
      </div>
    </div>
  </div>
</body>
</html>