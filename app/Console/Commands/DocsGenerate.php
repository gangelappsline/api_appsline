<?php

namespace App\Console\Commands;

use App\Support\OpenApi;
use Illuminate\Console\Command;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;

/**
 * Valida y exporta la especificación OpenAPI que se sirve en /docs.
 *
 *   php artisan docs:generate              valida y regenera openapi/openapi.json
 *   php artisan docs:generate --check      sólo comprueba (ideal para CI)
 *   php artisan docs:generate --public     copia también a public/docs
 *   php artisan docs:generate --coverage   audita rutas reales vs. documentadas
 */
class DocsGenerate extends Command
{
    protected $signature = 'docs:generate
        {--check : Valida la spec y el export sin escribir ficheros (exit 1 si hay problemas)}
        {--public : Copia también el JSON y el YAML a public/docs}
        {--coverage : Informa de rutas de la API no documentadas y de paths huérfanos en la spec}
        {--force : Escribe aunque la validación detecte problemas}
        {--output=* : Rutas adicionales donde volcar el JSON}';

    protected $description = 'Valida y exporta la documentación OpenAPI/Swagger de la API';

    public function handle(): int
    {
        $checking = (bool) $this->option('check');
        $problems = [];

        try {
            $spec = OpenApi::load(fresh: true);
            $problems = OpenApi::validate($spec, failOnProblems: false);
        } catch (\Throwable $e) {
            $spec = [];
            $problems[] = $e->getMessage();
        }

        $stats = $spec === [] ? null : OpenApi::statistics($spec);

        $this->info('Fuente: '.$this->relative(OpenApi::sourcePath()).
            ' · export: '.$this->relative(OpenApi::exportPath()).
            ' · parser YAML: '.(OpenApi::yamlParserAvailable() ? 'sí' : 'no'));

        $this->info($stats === null
            ? 'Spec: no legible'
            : sprintf('Spec: %d paths · %d operaciones · %d schemas · %d tags',
                $stats['paths'], $stats['operations'], $stats['schemas'], count($stats['tags'])));

        $exportProblem = OpenApi::exportStatus();

        if ($exportProblem !== null) {
            $this->warn($exportProblem);
        }

        if ($problems === []) {
            $this->info('Validación estructural: OK');
        } else {
            $this->error(sprintf('Validación estructural: %d problema(s)', count($problems)));

            $this->table(
                ['#', 'problema'],
                array_map(
                    static fn (string $problem, int $index): array => [$index + 1, $problem],
                    array_values($problems),
                    array_keys($problems)
                )
            );
        }

        if ($this->option('coverage')) {
            // Informativo: las rutas reales sin documentar se listan como aviso.
            // No hacen fallar --check porque las rutas declaradas y no implementadas
            // viven a propósito en docs/API.md, no en la spec.
            $this->coverage($spec);
        }

        if ($checking) {
            if ($problems !== [] || $exportProblem !== null) {
                $this->error('docs:generate --check: falla (problemas o export desactualizado).');

                return self::FAILURE;
            }

            $this->info('docs:generate --check: OK.');

            return self::SUCCESS;
        }

        if ($problems !== [] && ! $this->option('force')) {
            $this->error('Nada escrito. Corrige los problemas o usa --force.');

            return self::FAILURE;
        }

        return $this->write($spec) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Escribe el export JSON, los `--output` extra y la copia a `public/docs`.
     */
    protected function write(array $spec): bool
    {
        $written = 0;

        if ($spec !== [] && OpenApi::yamlParserAvailable()) {
            $this->writeTo($spec, OpenApi::exportPath());
            $written++;
        } elseif ($spec !== []) {
            $this->warn(sprintf(
                'Sin symfony/yaml no se puede regenerar el JSON desde el YAML: se omite %s. '.
                'Instala las dev-dependencies (composer install) y vuelve a generar.',
                OpenApi::exportPath()
            ));
        }

        foreach ((array) $this->option('output') as $extra) {
            if ($spec !== [] && is_string($extra) && $extra !== '') {
                $this->writeTo($spec, $extra);
                $written++;
            }
        }

        // La copia a public/ es volcado de ficheros: funciona también sin parser de YAML.
        if ($this->option('public')) {
            $written += $this->copyToPublic();
        }

        if ($written === 0) {
            $this->error('No se ha escrito ningún fichero.');

            return false;
        }

        return true;
    }

    protected function writeTo(array $spec, string $path): void
    {
        OpenApi::writeExport($spec, $path);

        $this->info('Escrito '.$this->relative($path));
    }

    /**
     * Copia los artefactos versionados a public/docs para servirlos estáticos.
     */
    protected function copyToPublic(): int
    {
        $map = [
            OpenApi::exportPath() => (string) config('docs.public_export.json', ''),
            OpenApi::sourcePath() => (string) config('docs.public_export.yaml', ''),
        ];

        $copied = 0;

        foreach ($map as $from => $to) {
            if ($to === '') {
                $this->warn('Sin destino configurado para la copia pública (docs.public_export).');

                continue;
            }

            if (! is_readable($from)) {
                $this->warn('Origen no legible, se omite: '.$this->relative($from));

                continue;
            }

            if (! is_dir($dir = dirname($to))) {
                mkdir($dir, 0775, true);
            }

            copy($from, $to);
            $this->info('Copiado a '.$this->relative($to));
            $copied++;
        }

        return $copied;
    }

    /**
     * Auditoría: rutas declaradas en la app vs. rutas documentadas en la spec.
     *
     * Volcado informativo (no afecta al código de salida de `--check`):
     * `routes/api.php` usa `apiResource()`, que publica verbos que los controladores
     * no implementan; ésos se catalogan en docs/API.md a propósito.
     *
     * @param  array  $spec
     */
    protected function coverage(array $spec): void
    {
        $documented = [];
        $documentedPaths = [];

        foreach ((array) ($spec['paths'] ?? []) as $path => $item) {
            $normalized = static::normalizePath((string) $path);
            $documentedPaths[$normalized] = true;

            foreach (array_keys((array) $item) as $key) {
                if (in_array(strtolower((string) $key), OpenApi::METHODS, true)) {
                    $documented[strtoupper((string) $key).' '.$normalized] = true;
                }
            }
        }

        $rows = [];
        $missing = [];
        $realPaths = [];

        /** @var RouteInstance $route */
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            // Sólo el árbol de la API: `api` y `api/...`.
            if ($uri !== 'api' && ! str_starts_with($uri, 'api/')) {
                continue;
            }

            $normalized = static::normalizePath('/'.ltrim(substr($uri, 3), '/'));
            $realPaths[$normalized] = true;

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $isDocumented = isset($documented[$method.' '.$normalized]);

                if (! $isDocumented) {
                    $missing[] = $method.' /'.ltrim($uri, '/');
                }

                $rows[] = [
                    $method,
                    '/'.ltrim($uri, '/'),
                    $isDocumented ? 'sí' : 'NO',
                    $this->middlewareLabel($route),
                ];
            }
        }

        if ($rows === []) {
            $this->warn('No se han encontrado rutas api/*.');

            return;
        }

        $orphans = array_values(array_diff(array_keys($documentedPaths), array_keys($realPaths)));

        usort($rows, static function (array $a, array $b): int {
            // Lo no documentado primero, luego por URI y método.
            return [$a[2] === 'NO' ? 0 : 1, $a[1], $a[0]]
                <=> [$b[2] === 'NO' ? 0 : 1, $b[1], $b[0]];
        });

        $this->newLine();
        $this->info(sprintf(
            'Cobertura: %d/%d operaciones de la API documentadas',
            count($rows) - count($missing),
            count($rows)
        ));
        $this->table(['Método', 'URI', 'Doc', 'Middleware'], $rows);

        foreach ($missing as $item) {
            $this->warn('Ruta sin documentar (añádela a openapi/openapi.yaml o a docs/API.md): '.$item);
        }

        foreach ($orphans as $orphan) {
            $this->warn('Path documentado sin ruta real, bórralo de la spec: '.$orphan);
        }

        if ($missing === [] && $orphans === []) {
            $this->info('Cobertura: la spec se corresponde con las rutas de la API.');
        }
    }

    /**
     * Middleware de la ruta, formateado para la tabla (sólo cadenas).
     */
    protected function middlewareLabel(RouteInstance $route): string
    {
        $labels = array_values(array_filter(
            $route->gatherMiddleware(),
            static fn ($middleware): bool => is_string($middleware)
        ));

        return $labels === [] ? '-' : implode(',', array_unique($labels));
    }

    /**
     * Normaliza un path para poder comparar: `{task_id}` -> `{}`, sin barra final.
     */
    protected static function normalizePath(string $path): string
    {
        $path = '/'.ltrim(trim($path), '/');
        $path = (string) preg_replace('/\{[^}]+\}/', '{}', $path);

        return rtrim($path, '/') ?: '/';
    }

    protected function relative(string $path): string
    {
        return str_replace(base_path().'/', '', $path);
    }
}
