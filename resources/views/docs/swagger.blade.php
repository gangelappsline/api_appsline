<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>

    @if ($error)
        <style>
            body { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; background: #f7f7f8; margin: 0; padding: 3rem; color: #1b1b1f; }
            .box { max-width: 60rem; margin: 0 auto; background: #fff; border: 1px solid #f1c0c5; border-left: 4px solid #d4343f; border-radius: .5rem; padding: 1.5rem; }
            h1 { font-size: 1.1rem; margin: 0 0 .75rem; }
            pre { white-space: pre-wrap; margin: 0; }
        </style>
    @else
        <link rel="stylesheet" href="{{ $swaggerBase }}/swagger-ui.css">
        <style>
            :root { --nav-h: 54px; }
            html, body { margin: 0; padding: 0; height: 100%; }
            body { background: #fafafa; }

            .docs-nav {
                position: sticky; top: 0; z-index: 20;
                display: flex; flex-wrap: wrap; align-items: center; gap: .75rem;
                min-height: var(--nav-h);
                padding: .5rem 1rem;
                background: #1b1b1f; color: #f5f5f5;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: .82rem;
            }
            .docs-nav strong { font-size: .95rem; margin-right: .25rem; }
            .docs-nav .sep { opacity: .35; }
            .docs-nav a {
                color: #ffd7a8; text-decoration: none;
                border: 1px solid rgba(255, 255, 255, .18);
                border-radius: 999px; padding: .18rem .65rem;
            }
            .docs-nav a:hover { background: rgba(255, 255, 255, .1); }
            .docs-nav .meta { margin-left: auto; display: flex; gap: .6rem; opacity: .75; }
            .docs-nav .meta code { font-size: .75rem; }

            .docs-hint {
                background: #fff8e6; border-bottom: 1px solid #f0dfa8;
                color: #6b5200; padding: .6rem 1rem;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: .82rem; line-height: 1.5;
            }
            .docs-hint.warn { background: #fdeceb; border-bottom-color: #f2c2c0; color: #8c2118; }
            .docs-hint code { background: rgba(0, 0, 0, .06); padding: .05rem .3rem; border-radius: .25rem; }

            .swagger-ui .topbar { display: none; }
            .swagger-ui .info { margin: 1.5rem 0 1rem; }
            .swagger-ui .info .title { font-size: 1.6rem; }
            .swagger-ui .opblock-tag { font-size: 1.15rem; }
        </style>
    @endif
</head>
<body>
@if ($error)
    <div class="box">
        <h1>No se pudo cargar la especificación OpenAPI</h1>
        <pre>{{ $error }}</pre>
        <p>Comprueba <code>openapi/openapi.yaml</code> y ejecuta <code>php artisan docs:generate --check</code>.</p>
    </div>
@else
    <nav class="docs-nav">
        <strong>{{ $title }}</strong>
        <span class="sep">/</span>
        <a href="{{ $specUrl }}">openapi.json</a>
        <a href="{{ $yamlUrl }}">openapi.yaml</a>
        <a href="{{ route('docs.redoc') }}">Ver en ReDoc</a>
        <span class="meta">
            <code>{{ $stats['operations'] ?? 0 }} operaciones · {{ $stats['paths'] ?? 0 }} paths · {{ $stats['schemas'] ?? 0 }} schemas</code>
            <code>assets: {{ $assetsMode }}</code>
        </span>
    </nav>

    @if ($warning)
        <div class="docs-hint warn">{{ $warning }}</div>
    @endif

    <div class="docs-hint">
        Para probar los endpoints protegidos:
        <strong>POST /api/login</strong> → copia <code>data.token</code> → botón
        <strong>Authorize</strong> (arriba a la derecha) → pégalo sin el prefijo <code>Bearer</code>.
        El token se guarda en este navegador (<code>persistAuthorization</code>).
    </div>

    <div id="swagger-ui"></div>

    <script src="{{ $swaggerBase }}/swagger-ui-bundle.js" crossorigin></script>
    <script src="{{ $swaggerBase }}/swagger-ui-standalone-preset.js" crossorigin></script>
    <script>
        window.ui = SwaggerUIBundle({
            url: @json($specUrl),
            dom_id: '#swagger-ui',
            deepLinking: true,
            displayRequestDuration: true,
            displayOperationId: true,
            docExpansion: @json($docExpansion),
            defaultModelsExpandDepth: 1,
            defaultModelExpandDepth: 2,
            maxDisplayedTags: null,
            filter: true,
            tryItOutEnabled: true,
            persistAuthorization: true,
            withCredentials: true,
            validatorUrl: null,
            presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
            plugins: [SwaggerUIBundle.plugins.DownloadUrl],
            layout: 'StandaloneLayout',
            requestInterceptor: function (req) {
                req.headers['Accept'] = 'application/json';
                // Evita el redirect 302 de las peticiones de navegador sin token.
                return req;
            },
            responseInterceptor: function (res) {
                return res;
            },
        });
    </script>
@endif
</body>
</html>
