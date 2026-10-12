<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · ReDoc</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .docs-nav {
            display: flex; flex-wrap: wrap; align-items: center; gap: .75rem;
            padding: .5rem 1rem; background: #1b1b1f; color: #f5f5f5; font-size: .82rem;
        }
        .docs-nav strong { font-size: .95rem; }
        .docs-nav a {
            color: #ffd7a8; text-decoration: none;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px; padding: .18rem .65rem;
        }
        .docs-nav a:hover { background: rgba(255, 255, 255, .1); }
        .docs-nav .meta { margin-left: auto; opacity: .75; }
        .docs-error { padding: 2rem; background: #fdeceb; color: #8c2118; font-family: ui-monospace, monospace; white-space: pre-wrap; }
    </style>
</head>
<body>
<nav class="docs-nav">
    <strong>{{ $title }}</strong>
    <a href="{{ route('docs.ui') }}">Swagger UI (interactivo)</a>
    <a href="{{ $specUrl }}">openapi.json</a>
    <a href="{{ $yamlUrl }}">openapi.yaml</a>
    <span class="meta">{{ $stats['operations'] ?? 0 }} operaciones</span>
</nav>

@if ($error)
    <div class="docs-error">{{ $error }}</div>
@else
    <div id="redoc-container"></div>

    <script src="{{ $redocBase }}/redoc.standalone.js" crossorigin></script>
    <script>
        Redoc.init(@json($specUrl), {
            scrollYOffset: 44,
            theme: {
                colors: { primary: { main: '#d03801' } },
                sidebar: { width: '320px', backgroundColor: '#f7f7f8' },
                typography: { fontSize: '14px' },
            },
            hideDownloadButton: false,
            expandDefaultServer: true,
            expandDefaultTag: false,
            pathInMiddlePanel: true,
            nativeScrollbars: true,
            unstable_ignoreMimeCorruption: true,
        }, document.getElementById('redoc-container'));
    </script>
@endif
</body>
</html>
