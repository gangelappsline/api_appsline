<?php

// config/appsline.php — identidad de marca para todos los correos
return [

    'name'     => 'Appsline',
    'tagline'  => env('APPSLINE_TAGLINE', 'Estrategia, Innovación y Desarrollo Tecnológico'),
    'domain'   => 'appsline.com.mx',
    'url'      => env('APP_URL', 'https://appsline.com.mx'),

    // Debe ser una URL ABSOLUTA (https) para que se vea en el cliente de correo.
    // PNG de 80x80 exportado del logo (isotipo del cerebro).
    'logo'     => env('APPSLINE_LOGO', rtrim(env('APP_URL', 'https://appsline.com.mx'), '/') . '/images/logo-full-transparent.webp'),

    // Ruta pública del theme CSS que el layout inyecta en el <head>
    'theme'    => env('APPSLINE_THEME', 'themes/appsline.css'),

    'font'     => "Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif",

    'company'  => 'appsline S.L. · Calle Mayor 42, 28013 Madrid',

    'colors'   => [
        'primary'      => '#0B47C4',
        'primary_dark' => '#083AA0',
        'accent'       => '#3BA9F0',
        'ink'          => '#14181D',
        'body'         => '#4A5568',
        'muted'        => '#8A94A6',
        'bg'           => '#F4F7FB',
        'border'       => '#E6ECF5',
        'navy'         => '#0A1733',
    ],

    // Redes del footer (sin imágenes: texto sobre círculo CSS)
    'social'   => [
        ['label' => 'in', 'name' => 'LinkedIn', 'url' => 'https://linkedin.com/company/appsline'],
        ['label' => 'X',  'name' => 'X',        'url' => 'https://x.com/appsline'],
        ['label' => 'f',  'name' => 'Facebook', 'url' => 'https://facebook.com/appsline'],
    ],

    'links'    => [
        'Panel'               => '/panel',
        'Documentaci&oacute;n' => '/docs',
        'Soporte'             => '/soporte',
        'Estado del servicio' => '/status',
    ],
];
