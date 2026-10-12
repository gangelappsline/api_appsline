<?php

return [
    'enabled' => filter_var(env('DOCS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'environments' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DOCS_ENVIRONMENTS', 'local,development,testing,staging')),
    ))),
];
