<?php

// config/tasks.php — Ajustes de tareas y de sus notificaciones por correo.

return [

    /*
    |--------------------------------------------------------------------------
    | URL del frontend
    |--------------------------------------------------------------------------
    |
    | URL base de la aplicación donde los usuarios ven sus tareas. Los
    | correos enlazan a "{frontend_url}/projects/{id}/tasks/{id}".
    | Si el frontend vive en el mismo dominio que la API, basta APP_URL.
    |
    */
    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost')),

    /*
    |--------------------------------------------------------------------------
    | Notificaciones por correo
    |--------------------------------------------------------------------------
    */
    'notifications' => [

        // true = también se avisa cuando alguien se asigna una tarea a sí
        // mismo (p. ej. al crear una subtarea propia). Por defecto no,
        // para no generar ruido en la bandeja de entrada.
        'notify_self_assignment' => env('TASK_NOTIFY_SELF_ASSIGNMENT', false),

        // Enviar a cada usuario su resumen semanal personal (viernes).
        'weekly_report_to_users' => env('TASK_WEEKLY_REPORT_TO_USERS', true),

        // Correos que reciben el reporte semanal GENERAL (resumen por
        // usuario de todo el equipo). Separados por comas en el .env:
        // TASK_WEEKLY_REPORT_RECIPIENTS="jefatura@appsline.com.mx,pm@appsline.com.mx"
        'weekly_report_recipients' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TASK_WEEKLY_REPORT_RECIPIENTS', ''))
        ))),

    ],

];
