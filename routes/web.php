<?php

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Documentación de la API (OpenAPI / Swagger UI + ReDoc)
|--------------------------------------------------------------------------
|
| Se sirve desde el grupo `web` para poder abrirla en el navegador sin token:
| DocsController responde 404 según config/docs.php (flag `enabled` y
| `DOCS_ENVIRONMENTS`), por lo que no hace falta condicionar el registro.
|
| /docs               Swagger UI  (interactivo)
| /docs/redoc         ReDoc       (lectura)
| /docs/openapi.json  spec JSON   (la que consumen Swagger UI / Postman / codegen)
| /docs/openapi.yaml  spec YAML   (el fichero fuente)
|
*/

Route::group(['prefix' => trim((string) config('docs.routes.prefix', 'docs'), '/')], function () {
    Route::get('/', [DocsController::class, 'ui'])
        ->name('docs.ui');

    Route::get((string) config('docs.routes.redoc', 'redoc'), [DocsController::class, 'redoc'])
        ->name('docs.redoc');

    Route::get((string) config('docs.routes.json', 'openapi.json'), [DocsController::class, 'json'])
        ->name('docs.json');

    Route::get((string) config('docs.routes.yaml', 'openapi.yaml'), [DocsController::class, 'yaml'])
        ->name('docs.yaml');
});
