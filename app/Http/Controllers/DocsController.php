<?php

namespace App\Http\Controllers;

use App\Support\OpenApi;
use Illuminate\Http\Request;
use Symfony\Component\Yaml\Yaml;

/**
 * Sirve la documentación de la API: Swagger UI, ReDoc y la especificación
 * OpenAPI en JSON/YAML.
 *
 * No requiere dependencias en composer: el spec se mantiene a mano en
 * `openapi/openapi.yaml` y se sirve tal cual (o desde el export JSON).
 */
class DocsController extends Controller
{
    /**
     * Swagger UI (interactivo).
     */
    public function ui(Request $request)
    {
        $this->ensureAccessible();

        return response()->view('docs.swagger', $this->viewData($request));
    }

    /**
     * ReDoc: misma spec, formato de lectura de una sola página.
     */
    public function redoc(Request $request)
    {
        $this->ensureAccessible();

        return response()->view('docs.redoc', $this->viewData($request));
    }

    /**
     * La especificación completa en JSON.
     *
     * Query: `?pretty=1` para indentarla, `?fresh=1` para saltarse la caché.
     */
    public function json(Request $request)
    {
        $this->ensureAccessible();

        $spec = $this->loadOrAbort($request);

        return response($this->jsonContent($spec, $request), $this->status(), $this->jsonHeaders($request))
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    /**
     * El fichero fuente YAML tal cual, para quien lo quiera versionar/importar.
     */
    public function yaml(Request $request)
    {
        $this->ensureAccessible();

        $source = OpenApi::sourcePath();

        if (is_readable($source)) {
            $content = (string) file_get_contents($source);
        } elseif (OpenApi::yamlParserAvailable()) {
            // Sólo posible en desarrollo: se regenera el YAML desde la spec cargada.
            $content = Yaml::dump($this->loadOrAbort($request), 20, 2);
        } else {
            abort(404, "El fichero {$source} no está disponible y no hay parser de YAML. ".
                'Usa /docs/openapi.json o ejecuta «php artisan docs:generate».');
        }

        return response($content, $this->status(), [
            'Content-Type' => 'application/yaml; charset=UTF-8',
            'X-OpenAPI-Source' => is_readable($source) ? 'file' : 'dump',
        ]);
    }

    /**
     * Datos comunes de las vistas.
     *
     * @return array<string, mixed>
     */
    protected function viewData(Request $request): array
    {
        $error = null;
        $stats = [];

        try {
            $spec = OpenApi::load($request->boolean('fresh'));
            OpenApi::validate($spec);
            $stats = OpenApi::statistics($spec);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $cdn = (array) config('docs.ui.cdn');
        $local = (array) config('docs.ui.local');
        $mode = (string) config('docs.ui.assets', 'cdn');

        return [
            'title' => (string) config('docs.spec.title', 'API docs'),
            'specUrl' => route('docs.json'),
            'yamlUrl' => route('docs.yaml'),
            'swaggerBase' => $mode === 'local' ? ($local['swagger'] ?? '/vendor/swagger-ui') : ($cdn['swagger'] ?? ''),
            'redocBase' => $mode === 'local' ? ($local['redoc'] ?? '/vendor/swagger-ui') : ($cdn['redoc'] ?? ''),
            'assetsMode' => $mode,
            'docExpansion' => (string) config('docs.ui.doc_expansion', 'list'),
            'stats' => $stats,
            'error' => $error,
            'warning' => $this->warning(),
        ];
    }

    /**
     * Aviso suave: export JSON desactualizado o falta de parser de YAML en producción.
     */
    protected function warning(): ?string
    {
        if (app()->environment('production') || config('docs.spec.prefer_export')) {
            return null;
        }

        if (! OpenApi::yamlParserAvailable()) {
            return 'Se está sirviendo el export openapi/openapi.json porque symfony/yaml no está '.
                'disponible (composer install --no-dev). Regenera el export con «php artisan docs:generate» '.
                'cada vez que cambie el YAML.';
        }

        return OpenApi::exportStatus();
    }

    /**
     * @return array<string, string>
     */
    protected function jsonHeaders(Request $request): array
    {
        $headers = [
            // La UI y las herramientas externas (Postman, inspectores de CI) deben poder leerla.
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
            'X-OpenAPI-Source' => OpenApi::yamlParserAvailable() && ! config('docs.spec.prefer_export')
                ? 'yaml:openapi/openapi.yaml'
                : 'json:openapi/openapi.json',
        ];

        $ttl = (int) config('docs.cache.ttl', 0);

        if ($ttl > 0 && ! $request->boolean('fresh') && ! app()->hasDebugModeEnabled()) {
            $headers['Cache-Control'] = 'public, max-age='.$ttl;
        } else {
            $headers['Cache-Control'] = 'no-store, max-age=0';
        }

        return $headers;
    }

    protected function jsonContent(array $spec, Request $request): string
    {
        return OpenApi::toJson($spec, $request->boolean('pretty'));
    }

    protected function status(): int
    {
        return 200;
    }

    /**
     * Carga la spec o responde un 500 con el mensaje accionable.
     *
     * @return array
     */
    protected function loadOrAbort(Request $request): array
    {
        try {
            return OpenApi::load($request->boolean('fresh'));
        } catch (\Throwable $e) {
            abort(500, 'No se pudo cargar la especificación OpenAPI: '.$e->getMessage());
        }
    }

    /**
     * Puerta de acceso: desactivado o entorno no permitido => 404.
     */
    protected function ensureAccessible(): void
    {
        if (! config('docs.enabled')) {
            abort(404, 'La documentación de la API está desactivada (DOCS_ENABLED=false).');
        }

        $allowed = (array) config('docs.environments', []);

        if ($allowed !== [] && ! in_array(app()->environment(), $allowed, true)) {
            abort(404, sprintf(
                'La documentación no está disponible en el entorno "%s" (DOCS_ENVIRONMENTS=%s).',
                app()->environment(),
                implode(',', $allowed)
            ));
        }
    }
}
