<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puerta de acceso a la referencia de la API (/docs y /docs/openapi.json).
 *
 * Cuando bloquea la petición devuelve 404, pero nunca "en silencio": deja el
 * motivo exacto en el log y, con APP_DEBUG activado, también en la respuesta.
 * Así un `config:cache` obsoleto o un APP_ENV fuera de la lista se detecta en
 * segundos en vez de parecer "Scramble no genera nada".
 *
 * Reglas:
 *  - DOCS_ENABLED=false              -> bloqueado.
 *  - DOCS_ENVIRONMENTS vacío o "*"   -> se sirve en cualquier entorno.
 *  - DOCS_ENVIRONMENTS con lista     -> sólo esos entornos (producción queda fuera por defecto).
 *  - config/docs.php sin cargar      -> se sirve en local/development/testing y se bloquea
 *    fuera de ahí (típico de un bootstrap/cache/config.php obsoleto).
 */
class EnsureDocumentationIsAccessible
{
    public function handle(Request $request, Closure $next): Response
    {
        $reason = static::blockReason();

        if ($reason === null) {
            return $next($request);
        }

        Log::warning('Documentación de la API bloqueada en '.$request->getPathInfo().': '.$reason);

        // Fuera de debug la respuesta queda genérica para no publicar detalles de configuración.
        abort(404, config('app.debug') ? $reason : '');
    }

    /**
     * Motivo por el que la documentación no debe servirse, o null si está permitida.
     */
    public static function blockReason(): ?string
    {
        $enabled = config('docs.enabled');
        $environments = config('docs.environments');

        if ($enabled === null && $environments === null) {
            return app()->environment(['local', 'development', 'testing'])
                ? null
                : 'config/docs.php no está cargado: hay un bootstrap/cache/config.php obsoleto. Ejecuta `php artisan config:clear` (y `php artisan config:cache` si lo usas).';
        }

        if ($enabled === false) {
            return 'DOCS_ENABLED está en false en .env. Cámbialo a DOCS_ENABLED=true y ejecuta `php artisan config:clear`.';
        }

        if (! is_array($environments) || $environments === [] || in_array('*', $environments, true)) {
            return null;
        }

        $current = app()->environment();

        if (! in_array($current, $environments, true)) {
            return sprintf(
                'El entorno actual "%s" no está en DOCS_ENVIRONMENTS (%s). Añádelo en .env, usa DOCS_ENVIRONMENTS=* para servir siempre la documentación, y ejecuta `php artisan config:clear`.',
                $current,
                implode(', ', $environments),
            );
        }

        return null;
    }
}
