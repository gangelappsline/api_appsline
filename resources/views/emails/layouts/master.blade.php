{{-- ─────────────────────────────────────────────────────────────────────────
Layout único de todos los correos de appsline.
El header y el footer son parciales: solo cambia @yield('content').
Uso: @extends('emails.layouts.master') + @section('content')
────────────────────────────────────────────────────────────────────────── --}}
@php($theme = config('appsline.theme', 'themes/appsline.css'))
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="only light">
    <meta name="supported-color-schemes" content="only light">
    <title>{{ $subject ?? config('app.name') }}</title>

    <!--[if mso]>
    <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch><o:AllowPNG/></o:OfficeDocumentSettings></xml>
    <![endif]-->

    @stack('styles')

    {{-- Gmail ignora
    <link rel="stylesheet">: el CSS se imprime en el head --}}
    <style type="text/css">
        @if (file_exists(public_path($theme)))
            {!! file_get_contents(public_path($theme)) !!}
        @endif
    </style>
</head>

<body style="margin:0; padding:0; width:100%!important; background-color:{{ config('appsline.colors.bg') }};">

    {{-- Preheader: texto de apoyo que se ve en la bandeja de entrada --}}
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; opacity:0; color:transparent;">
        {{ $preheader ?? '' }}
    </div>

    <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" class="outer"
        style="background-color:{{ config('appsline.colors.bg') }};">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <table role="presentation" class="wrapper" width="600" border="0" cellpadding="0" cellspacing="0"
                    style="width:600px; max-width:600px;">
                    <tr>
                        <td>

                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0"
                                class="card" style="background-color:#FFFFFF; border-radius:18px; overflow:hidden;">

                                {{-- ░░ HEADER ░░ --}}
                                @include('emails.layouts._header')

                                {{-- ░░ CONTENIDO (única parte que cambia) ░░ --}}
                                <tr>
                                    <td class="px py" style="padding:40px 48px 44px 48px;">
                                        @yield('content')
                                    </td>
                                </tr>

                                {{-- ░░ FOOTER ░░ --}}
                                @include('emails.layouts._footer')

                            </table>

                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>

</html>