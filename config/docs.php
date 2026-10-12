<?php

/*
|--------------------------------------------------------------------------
| Documentación de la API (OpenAPI / Swagger UI)
|--------------------------------------------------------------------------
|
| El contrato vivo de la API es `openapi/openapi.yaml` (YAML, para editarlo a
| mano y poder leer los diffs). De él se genera `openapi/openapi.json`, que es
| el que se sirve cuando no está disponible el parser de YAML (por ejemplo en
| producción con `composer install --no-dev`).
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | Si está desactivado, todas las rutas de documentación responden 404.
    |
    */
    'enabled' => filter_var(env('DOCS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Entornos en los que se sirve la documentación
    |--------------------------------------------------------------------------
    |
    | Lista separada por comas. Fuera de esa lista las rutas responden 404,
    | para no publicar el contrato de la API sin querer.
    |
    */
    'environments' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DOCS_ENVIRONMENTS', 'local,development,testing,staging'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Especificación
    |--------------------------------------------------------------------------
    */
    'spec' => [
        // Fuente de verdad (YAML).
        'source' => env('DOCS_SPEC_PATH', base_path('openapi/openapi.yaml')),

        // Export JSON generado (`php artisan docs:generate`) y versionado.
        'export' => base_path('openapi/openapi.json'),

        // Si es true se sirve siempre el export JSON, aunque el parser de YAML
        // esté disponible. Útil para validar en local el comportamiento de producción.
        'prefer_export' => filter_var(env('DOCS_PREFER_EXPORT', false), FILTER_VALIDATE_BOOLEAN),

        // Título de la pestaña del navegador.
        'title' => env('DOCS_TITLE', 'Appsline API · Documentación'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rutas
    |--------------------------------------------------------------------------
    |
    | /docs               -> Swagger UI (interactivo, "Try it out")
    | /docs/redoc         -> ReDoc (doc de lectura en una sola página)
    | /docs/openapi.json  -> spec en JSON, la consumen la UI y las herramientas
    | /docs/openapi.yaml  -> spec en YAML tal cual (fichero fuente)
    |
    */
    'routes' => [
        'prefix' => 'docs',
        'ui' => '',
        'redoc' => 'redoc',
        'json' => 'openapi.json',
        'yaml' => 'openapi.yaml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cacheo del parseo
    |--------------------------------------------------------------------------
    |
    | La clave de caché incluye el hash del fichero fuente: editar el YAML
    | invalida la entrada de forma automática. ttl = 0 desactiva la caché.
    |
    */
    'cache' => [
        'enabled' => filter_var(env('DOCS_CACHE', true), FILTER_VALIDATE_BOOLEAN),
        'ttl' => (int) env('DOCS_CACHE_TTL', 3600),
        'key' => 'docs.openapi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets de la UI
    |--------------------------------------------------------------------------
    */
    'ui' => [
        // 'cdn'   -> Swagger UI / ReDoc desde jsDelivr (cero assets en el repo).
        // 'local' -> se sirven desde public/vendor/swagger-ui (entornos sin salida
        //           a internet). Ver "Modo offline" en el README.
        'assets' => env('DOCS_UI_ASSETS', 'cdn'),

        'cdn' => [
            'swagger' => 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@5',
            'redoc' => 'https://cdn.jsdelivr.net/npm/redoc@2/bundles',
        ],

        'local' => [
            'swagger' => '/vendor/swagger-ui',
            'redoc' => '/vendor/swagger-ui',
        ],

        // none | list | full — cómo de desplegados aparecen los endpoints al abrir.
        'doc_expansion' => env('DOCS_UI_EXPANSION', 'list'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export estático para el servidor web / CDN
    |--------------------------------------------------------------------------
    |
    | `php artisan docs:generate --public` copia el JSON (y el YAML) a public/docs.
    | Al existir el fichero, el servidor web lo sirve en la misma URL que la ruta
    | dinámica (/docs/openapi.json), ahorrando el parseo. Regenera en el deploy.
    |
    */
    'public_export' => [
        'json' => public_path('docs/openapi.json'),
        'yaml' => public_path('docs/openapi.yaml'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validación
    |--------------------------------------------------------------------------
    |
    | Chequeos estructurales que ejecutan `docs:generate` y el loader antes de
    | servir la spec. Con `fail_on_problems` en true, una spec inválida lanza
    | una excepción en lugar de servirse rota.
    |
    */
    'validation' => [
        'fail_on_problems' => filter_var(env('DOCS_FAIL_ON_PROBLEMS', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
