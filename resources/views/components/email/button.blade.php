@props([
    'href'  => '#',
    'label' => 'Ver más',
    'ghost' => false,         // true = botón secundario con borde
])

@php
    $bg      = $ghost ? '#FFFFFF' : config('appsline.colors.primary');
    $color   = $ghost ? config('appsline.colors.primary') : '#FFFFFF';
    $border  = $ghost ? 'border:1px solid #C9DBFB;' : '';
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn{{ $ghost ? ' btn-ghost' : '' }}" style="margin:6px 0 4px 0;">
    <tr>
        <td align="center" bgcolor="{{ $bg }}" style="border-radius:12px; {{ $border }}">
            <a href="{{ $href }}" target="_blank"
               style="display:inline-block; font-family:{{ config('appsline.font') }}; background-color:{{ $bg }}; background-image:{{ $ghost ? 'none' : 'linear-gradient(135deg,#1266E8 0%,#0B47C4 60%,#083AA0 100%)' }}; border-radius:12px; color:{{ $color }}!important; font-size:16px; font-weight:700; padding:15px 30px; text-align:center; text-decoration:none;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
