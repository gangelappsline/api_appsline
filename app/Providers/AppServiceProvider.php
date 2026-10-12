<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Si el paquete no está instalado (vendor sin actualizar) la aplicación sigue
        // funcionando y el motivo queda en el log en vez de un error fatal en cada request.
        if (! class_exists(Scramble::class)) {
            Log::warning(
                'dedoc/scramble no está instalado: la documentación de /docs no se registrará. '
                .'Ejecuta `composer install` (o `composer require dedoc/scramble`) y `php artisan config:clear`.'
            );

            return;
        }

        // Mueve la referencia de Scramble de /docs/api a /docs (y el OpenAPI a /docs/openapi.json).
        Scramble::configure()->expose(
            ui: fn (Router $router, $action) => $router->get('docs', $action)->name('scramble.docs.ui'),
            document: fn (Router $router, $action) => $router->get('docs/openapi.json', $action)
                ->name('scramble.docs.document'),
        );
    }
}
