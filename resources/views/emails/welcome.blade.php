@extends('emails.layouts.master')

@section('content')

    <span class="badge" style="display:inline-block; background-color:#EEF4FF; border:1px solid #C9DBFB; border-radius:999px; color:{{ config('appsline.colors.primary') }}; font-family:{{ config('appsline.font') }}; font-size:12px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; padding:6px 14px;">
        Cuenta creada
    </span>

    <div style="height:18px; line-height:18px; font-size:18px;">&nbsp;</div>

    <x-email.heading>¡Bienvenido a {{ config('appsline.name') }}, {{ $name }}!</x-email.heading>

    <p class="p" style="margin:0 0 16px 0; font-family:{{ config('appsline.font') }}; font-size:16px; line-height:26px; color:{{ config('appsline.colors.body') }};">
        Tu cuenta ya está lista. Desde ahora tienes un solo lugar para diseñar, desplegar y
        monitorear tus aplicaciones con inteligencia artificial.
    </p>

    <p class="p" style="margin:0 0 16px 0; font-family:{{ config('appsline.font') }}; font-size:16px; line-height:26px; color:{{ config('appsline.colors.body') }};">
        Actívala en el botón de abajo y elige tu contraseña para entrar al panel:
    </p>

    <div style="height:10px; line-height:10px; font-size:10px;">&nbsp;</div>

    <x-email.button href="{{ $activationUrl }}" label="Activar mi cuenta" />

    <div style="height:14px; line-height:14px; font-size:14px;">&nbsp;</div>

    <p class="small" style="margin:0 0 12px 0; font-family:{{ config('appsline.font') }}; font-size:13px; line-height:21px; color:{{ config('appsline.colors.muted') }};">
        Este enlace expira en 60 minutos. Si necesitas otro, escríbenos a soporte@appsline.com.mx.
    </p>

    <div style="height:24px; line-height:24px; font-size:24px;">&nbsp;</div>

    <x-email.panel title="Datos de acceso" :rows="[
        'Correo'    => $email,
        'Plan'      => $plan,
        'Workspace' => $workspace,
        'Fecha'     => now()->translatedFormat('d M Y'),
    ]" />

    <div style="height:28px; line-height:28px; font-size:28px;">&nbsp;</div>

    <x-email.heading level="h2">Primeros 3 pasos</x-email.heading>

    <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
        @foreach ($steps as $i => $step)
        <tr>
            <td width="26" valign="top" style="padding:6px 0 0 0;">
                <div style="width:22px; height:22px; line-height:22px; border-radius:999px; background-color:#EEF4FF; color:{{ config('appsline.colors.primary') }}; font-family:{{ config('appsline.font') }}; font-size:12px; font-weight:700; text-align:center;">
                    {{ $i + 1 }}
                </div>
            </td>
            <td valign="top" style="padding:0 0 14px 0; font-family:{{ config('appsline.font') }}; font-size:16px; line-height:26px; color:{{ config('appsline.colors.body') }};">
                {!! $step !!}
            </td>
        </tr>
        @endforeach
    </table>

    <div style="height:8px; line-height:8px; font-size:8px;">&nbsp;</div>

    <div class="divider" style="height:1px; border-top:1px solid #EDF1F7; font-size:0; line-height:0; margin:28px 0;">&nbsp;</div>

    <x-email.signature />

@endsection
