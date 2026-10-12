<?php

namespace App\Console\Commands;

use App\Http\Middleware\EnsureDocumentationIsAccessible;
use Dedoc\Scramble\Contracts\RouteProvider as RouteProviderContract;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\OperationBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Diagnóstico de extremo a extremo de la documentación OpenAPI.
 *
 * Reproduce en consola exactamente lo que ocurre cuando se pide /docs:
 * paquete instalado -> rutas registradas -> permiso de acceso -> cachés ->
 * generación real del documento. Cada fallo sale con el comando que lo arregla.
 */
class CheckApiDocumentation extends Command
{
    protected $signature = 'docs:check
        {--skip-generation : No genera el documento OpenAPI (revisa sólo rutas, acceso y cachés)}
        {--json : Imprime el informe en JSON, útil para CI}';

    protected $description = 'Diagnostica por qué la documentación de la API (Scramble) no se genera o no se sirve en /docs';

    /** @var list<array{check: string, status: string, detail: ?string, fix: ?string}> */
    private array $checks = [];

    public function handle(): int
    {
        $this->checkPackage();
        $this->checkRoutes();
        $this->checkAccess();
        $this->checkCaches();
        $this->checkDuplicateRouteNames();

        if (! $this->option('skip-generation')) {
            $this->checkGeneration();
        }

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'ok' => ! $this->hasProblems(),
                'checks' => $this->checks,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $this->hasProblems() ? self::FAILURE : self::SUCCESS;
        }

        return $this->render();
    }

    private function checkPackage(): void
    {
        if (! class_exists(Scramble::class)) {
            $this->markFailed('Paquete', 'dedoc/scramble no está instalado en vendor/', 'composer install (o composer require dedoc/scramble)');

            return;
        }

        $version = class_exists(\Composer\InstalledVersions::class)
            ? \Composer\InstalledVersions::getPrettyVersion('dedoc/scramble')
            : null;

        $this->markPassed('Paquete', 'dedoc/scramble'.($version ? " {$version}" : ' instalado'));
    }

    private function checkRoutes(): void
    {
        foreach ([
            'scramble.docs.ui' => 'Referencia (UI)',
            'scramble.docs.document' => 'Documento OpenAPI',
        ] as $name => $label) {
            $route = Route::getRoutes()->getByName($name);

            if ($route === null) {
                $this->markFailed(
                    $label,
                    "no hay ninguna ruta con el nombre {$name}",
                    'Revisa el Scramble::configure()->expose(...) de app/Providers/AppServiceProvider.php y ejecuta `php artisan route:clear`',
                );

                continue;
            }

            $this->markPassed($label, url('/'.ltrim($route->uri(), '/')));
        }
    }

    private function checkAccess(): void
    {
        $reason = EnsureDocumentationIsAccessible::blockReason();

        if ($reason !== null) {
            $this->markFailed('Acceso', $reason, 'Ajusta DOCS_ENABLED / DOCS_ENVIRONMENTS en .env y ejecuta `php artisan config:clear`');

            return;
        }

        $this->markPassed(
            'Acceso',
            sprintf('entorno "%s" permitido (DOCS_ENVIRONMENTS=%s)', app()->environment(), $this->environments()),
        );
    }

    private function environments(): string
    {
        $environments = config('docs.environments');

        return is_array($environments) && $environments !== [] ? implode(',', $environments) : '*';
    }

    private function checkCaches(): void
    {
        if (app()->configurationIsCached()) {
            $missing = array_values(array_filter(
                ['docs.enabled', 'docs.environments', 'scramble.api_path', 'scramble.middleware'],
                fn (string $key) => ! config()->has($key),
            ));

            if ($missing !== []) {
                $this->markFailed(
                    'Config cache',
                    'el config cacheado es anterior a esta integración (faltan '.implode(', ', $missing).')',
                    'php artisan config:clear && php artisan config:cache',
                );
            } else {
                $this->markPassed('Config cache', 'cacheada y al día');
            }
        } else {
            $this->markPassed('Config cache', 'sin cachear (se lee .env directamente)');
        }

        if (app()->routesAreCached()) {
            $this->markNote('Route cache', 'activa: si cambias rutas, vuelve a ejecutar `php artisan route:cache`');
        }

        $store = config('scramble.cache.store');
        $key = config('scramble.cache.key');

        if (is_string($store) && is_string($key) && rescue(fn () => cache()->store($store)->has($key.':default'), report: false)) {
            $this->markNote(
                'Cache del documento',
                "hay un documento OpenAPI cacheado en el store \"{$store}\" (puede estar obsoleto): php artisan scramble:clear",
            );
        }
    }

    private function checkDuplicateRouteNames(): void
    {
        $duplicated = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->groupBy(fn (string $name) => $name)
            ->filter(fn ($group) => $group->count() > 1)
            ->keys()
            ->all();

        if ($duplicated !== []) {
            $this->markFailed(
                'Nombres de ruta',
                'duplicados: '.implode(', ', $duplicated).' (rompen `php artisan route:cache` y `php artisan optimize`)',
                'Renombra uno de los recursos en routes/api.php, p.ej. ->names(\'public.projects\')',
            );

            return;
        }

        $this->markPassed('Nombres de ruta', 'sin duplicados');
    }

    private function checkGeneration(): void
    {
        if (! class_exists(Scramble::class)) {
            return;
        }

        try {
            $config = Scramble::getGeneratorConfig(Scramble::DEFAULT_API);

            $generator = new Generator(
                app(OperationBuilder::class),
                app(RouteProviderContract::class),
            );

            $result = $generator->generate($config);
            $document = $result->spec();
        } catch (Throwable $e) {
            $this->markFailed(
                'Generación',
                get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().')',
                'Corrige el controlador/recurso indicado; con APP_DEBUG=true Scramble vuelca la ruta exacta que falla',
            );

            return;
        }

        $paths = $document['paths'] ?? [];
        $operations = array_sum(array_map(
            fn (array $path) => count(array_filter($path, fn ($key) => $key !== 'servers', ARRAY_FILTER_USE_KEY)),
            $paths,
        ));

        if ($paths === []) {
            $this->markFailed(
                'Generación',
                'el documento se genera pero no contiene ninguna ruta',
                'Revisa scramble.api_path ('.var_export(config('scramble.api_path'), true).') contra el prefijo real de routes/api.php',
            );

            return;
        }

        $this->markPassed('Generación', count($paths).' rutas / '.$operations.' operaciones OpenAPI');

        $diagnostics = $result->diagnostics();

        if ($diagnostics->isNotEmpty()) {
            $shown = $diagnostics->take(3)
                ->map(fn ($diagnostic) => '['.$diagnostic->code().'] '.$diagnostic->message())
                ->implode(' · ');

            $remaining = $diagnostics->count() - 3;

            $this->markNote(
                'Avisos de Scramble',
                $diagnostics->count().' aviso(s): '.$shown.($remaining > 0 ? " · +{$remaining} más" : '')
                .' — detalle completo con `php artisan scramble:analyze`',
            );
        }
    }

    private function markPassed(string $check, string $detail): void
    {
        $this->checks[] = ['check' => $check, 'status' => 'ok', 'detail' => $detail, 'fix' => null];
    }

    private function markNote(string $check, string $detail): void
    {
        $this->checks[] = ['check' => $check, 'status' => 'aviso', 'detail' => $detail, 'fix' => null];
    }

    private function markFailed(string $check, string $detail, string $fix): void
    {
        $this->checks[] = ['check' => $check, 'status' => 'fallo', 'detail' => $detail, 'fix' => $fix];
    }

    private function hasProblems(): bool
    {
        return collect($this->checks)->contains(fn (array $check) => $check['status'] === 'fallo');
    }

    private function render(): int
    {
        $this->components->info('Documentación de la API en '.url('/docs'));

        foreach ($this->checks as $check) {
            $this->components->twoColumnDetail(
                $check['check'],
                match ($check['status']) {
                    'ok' => '<fg=green>OK</> '.$check['detail'],
                    'aviso' => '<fg=yellow>AVISO</> '.$check['detail'],
                    default => '<fg=red>FALLO</> '.$check['detail'],
                },
            );
        }

        $fixes = collect($this->checks)
            ->filter(fn (array $check) => $check['status'] === 'fallo')
            ->map(fn (array $check) => $check['fix'])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($fixes === []) {
            $this->newLine();
            $this->components->info('Todo en orden: abre '.url('/docs').' y '.url('/docs/openapi.json'));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->error('La documentación no se sirve correctamente');
        $this->components->bulletList($fixes);
        $this->newLine();
        $this->line('  Tras aplicar los cambios: <comment>php artisan optimize:clear</comment>');

        return self::FAILURE;
    }
}
