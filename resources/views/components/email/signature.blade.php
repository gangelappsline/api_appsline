@props([
    'name' => 'El equipo de '.config('appsline.name'),
    'role' => 'Soporte · '.config('appsline.domain'),
])

<table role="presentation" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td height="46" valign="middle">
            <img src="{{ $logo ?? config('appsline.logo') }}" height="42" alt="" style="display:block; height:42px; border:0; border-radius:12px;">
        </td>
        <td valign="middle" style="padding-left:12px;">
            <div style="font-family:{{ config('appsline.font') }}; font-size:15px; font-weight:700; color:{{ config('appsline.colors.ink') }};">{{ $name }}</div>
            <div style="font-family:{{ config('appsline.font') }}; font-size:13px; color:{{ config('appsline.colors.muted') }};">{{ $role }}</div>
        </td>
    </tr>
</table>
