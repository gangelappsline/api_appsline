<?php

use Illuminate\Support\Facades\Route;

it('no registra dos rutas con el mismo nombre (rompería route:cache)', function () {
    $duplicated = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->duplicates()
        ->values()
        ->all();

    expect($duplicated)->toBe([]);
});

it('expone la documentación en /docs y el documento en /docs/openapi.json', function () {
    expect(Route::has('scramble.docs.ui'))->toBeTrue()
        ->and(Route::has('scramble.docs.document'))->toBeTrue()
        ->and(Route::getRoutes()->getByName('scramble.docs.ui')->uri())->toBe('docs')
        ->and(Route::getRoutes()->getByName('scramble.docs.document')->uri())->toBe('docs/openapi.json');
});

it('aprueba el diagnóstico docs:check', function () {
    $this->artisan('docs:check --skip-generation')->assertSuccessful();
});

it('sigue sirviendo la documentación en testing aunque config/docs.php no esté cargado', function () {
    config()->offsetUnset('docs');

    $this->get('/docs/openapi.json')->assertOk();
});

it('admite DOCS_ENVIRONMENTS=* para servir la documentación en cualquier entorno', function () {
    config(['docs.environments' => ['*']]);

    $this->get('/docs/openapi.json')->assertOk();
});
