<?php

return [
    /*
     * Interruptor general de la referencia de la API (/docs y /docs/openapi.json).
     * DOCS_ENABLED=false apaga tanto la UI como el documento OpenAPI.
     */
    'enabled' => filter_var(env('DOCS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
     * Entornos (APP_ENV) en los que se sirve la documentación.
     * Usa DOCS_ENVIRONMENTS=* para servirla en cualquier entorno (incluido producción)
     * o déjalo vacío para el mismo efecto. Por defecto producción queda fuera.
     */
    'environments' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DOCS_ENVIRONMENTS', 'local,development,testing,staging')),
    ))),
];
