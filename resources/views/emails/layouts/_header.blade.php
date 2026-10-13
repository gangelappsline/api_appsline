{{-- ═══════════ HEADER ═══════════
     Variables sobre-escribibles: $logo, $homeUrl
────────────────────────────────────────────── --}}
<tr>
    <td style="padding:0; background-color:#FFFFFF;">
        {{-- Barra de degradado hecha con 3 celdas (compatible con Outlook) --}}
        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td height="4" bgcolor="{{ config('appsline.colors.primary_dark') }}" style="height:4px; line-height:4px; font-size:0;">&nbsp;</td>
                <td height="4" bgcolor="{{ config('appsline.colors.primary') }}" style="height:4px; line-height:4px; font-size:0;">&nbsp;</td>
                <td height="4" bgcolor="{{ config('appsline.colors.accent') }}" style="height:4px; line-height:4px; font-size:0;">&nbsp;</td>
            </tr>
        </table>

        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td class="px" style="padding:24px 32px; border-bottom:1px solid #EDF1F7;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td height="46" valign="middle">
                                <a href="{{ $homeUrl ?? config('appsline.url') }}" target="_blank" style="text-decoration:none;">
                                    <img src="{{ $logo ?? config('appsline.logo') }}" height="40" alt="{{ config('appsline.name') }}"
                                         class="logo" style="display:block; height:40px; border:0; outline:none; border-radius:12px;">
                                </a>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="hide-sm" align="right" valign="middle"
                    style="padding:24px 32px; border-bottom:1px solid #EDF1F7; font-family:{{ config('appsline.font') }}; font-size:13px; letter-spacing:.4px; color:{{ config('appsline.colors.muted') }};">
                    {{ config('appsline.tagline') }}
                </td>
            </tr>
        </table>
    </td>
</tr>
