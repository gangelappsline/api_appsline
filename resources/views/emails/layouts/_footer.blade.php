{{-- ═══════════ FOOTER ═══════════
     Variables sobre-escribibles: $unsubscribeUrl, $preferencesUrl
────────────────────────────────────────────── --}}
<tr>
    <td style="padding:0; background-color:{{ config('appsline.colors.navy') }};">
        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0"
               style="background-color:{{ config('appsline.colors.navy') }};">
            <tr>
                <td class="px" align="center" style="padding:34px 32px 30px 32px;">

                    {{-- Redes sociales sin imágenes (bulletproof) --}}
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="social" align="center">
                        <tr>
                            @foreach (config('appsline.social') as $social)
                            <td style="padding:0 5px;">
                                <a href="{{ $social['url'] }}" target="_blank"
                                   style="display:inline-block; width:34px; height:34px; line-height:34px; border-radius:999px; background-color:#16294F; color:#FFFFFF; font-size:13px; font-weight:700; text-align:center; text-decoration:none; font-family:{{ config('appsline.font') }};">
                                    {{ $social['label'] }}
                                </a>
                            </td>
                            @endforeach
                        </tr>
                    </table>

                    <div style="height:20px; line-height:20px; font-size:20px;">&nbsp;</div>

                    <div class="footer-links" style="font-family:{{ config('appsline.font') }}; font-size:13px; line-height:22px; color:#93A5C6;">
                        @foreach (config('appsline.links') as $label => $url)
                            <a href="{{ $url }}" style="color:#CFDCF5; text-decoration:none; margin:0 8px;">{{ $label }}</a>
                            @if (! $loop->last)<span style="color:#33456B;">|</span>@endif
                        @endforeach
                    </div>

                    <div style="height:14px; line-height:14px; font-size:14px;">&nbsp;</div>

                    <div style="font-family:{{ config('appsline.font') }}; font-size:12px; line-height:20px; color:#5F6F91;">
                        {{ config('appsline.company') }}<br>
                        Recibes este correo porque tienes una cuenta en {{ config('appsline.name') }}.<br>
                        <a href="{{ $preferencesUrl ?? config('appsline.url').'/preferencias' }}" style="color:#93A5C6; text-decoration:underline;">Preferencias</a>
                        &nbsp;&middot;&nbsp;
                        <a href="{{ $unsubscribeUrl ?? config('appsline.url').'/baja' }}" style="color:#93A5C6; text-decoration:underline;">Cancelar suscripci&oacute;n</a>
                    </div>

                    <div style="height:16px; line-height:16px; font-size:16px;">&nbsp;</div>

                    <div style="font-family:{{ config('appsline.font') }}; font-size:12px; line-height:18px; color:#3F5379;">
                        &copy; {{ date('Y') }} {{ config('appsline.name') }}. Todos los derechos reservados.
                    </div>

                </td>
            </tr>
        </table>
    </td>
</tr>
