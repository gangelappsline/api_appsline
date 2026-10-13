@props([
    'title'  => null,
    'accent' => false,        // true = fondo azul suave
    'rows'   => [],           // ['Etiqueta' => 'Valor', ...]
])

@php($bg = $accent ? '#EEF4FF' : config('appsline.colors.bg'))
@php($bd = $accent ? '#C9DBFB' : config('appsline.colors.border'))

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0"
       class="{{ $accent ? 'panel-accent' : 'panel' }}"
       style="background-color:{{ $bg }}; border:1px solid {{ $bd }}; border-radius:14px;">
    <tr>
        <td style="padding:20px 22px;">

            @if ($title)
                <div style="margin:0 0 12px 0; font-family:{{ config('appsline.font') }}; font-size:13px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:{{ $accent ? config('appsline.colors.primary') : config('appsline.colors.muted') }};">
                    {{ $title }}
                </div>
            @endif

            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                @foreach ($rows as $label => $value)
                <tr>
                    <td class="stack" width="45%" style="font-family:{{ config('appsline.font') }}; font-size:14px; line-height:22px; color:{{ config('appsline.colors.muted') }}; padding:5px 0;">
                        {{ $label }}
                    </td>
                    <td class="stack" align="right" style="font-family:{{ config('appsline.font') }}; font-size:14px; line-height:22px; font-weight:600; color:{{ config('appsline.colors.ink') }}; padding:5px 0;">
                        {!! $value !!}
                    </td>
                </tr>
                @endforeach
            </table>

        </td>
    </tr>
</table>
