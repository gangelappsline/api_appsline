<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loader, validador y exportador de la especificación OpenAPI del proyecto.
 *
 * La fuente de verdad es `openapi/openapi.yaml`. Cuando el parser de YAML está
 * disponible (instalación con dev-dependencies) se sirve siempre desde ahí, así
 * que editar el fichero se refleja al recargar el navegador. En caso contrario
 * se sirve el export JSON versionado (`openapi/openapi.json`), generado con
 * `php artisan docs:generate`.
 */
class OpenApi
{
    /** Extensión que marca desde qué revisión del YAML se generó un export. */
    public const SOURCE_HASH_KEY = 'x-source-sha1';

    /** @var array<string, array>|null */
    protected static ?array $memo = null;

    /** Métodos HTTP que cuentan como operación en la spec. */
    public const METHODS = ['get', 'put', 'post', 'patch', 'delete', 'options', 'head', 'trace'];

    public static function yamlParserAvailable(): bool
    {
        return class_exists(Yaml::class);
    }

    public static function sourcePath(): string
    {
        return (string) config('docs.spec.source', base_path('openapi/openapi.yaml'));
    }

    public static function exportPath(): string
    {
        return (string) config('docs.spec.export', base_path('openapi/openapi.json'));
    }

    /**
     * SHA-1 del fichero fuente. `null` si no está legible o no hay parser.
     */
    public static function sourceHash(): ?string
    {
        $path = static::sourcePath();

        return is_readable($path) ? (string) sha1_file($path) : null;
    }

    /**
     * Devuelve la spec como array.
     *
     * @return array
     *
     * @throws RuntimeException
     */
    public static function load(bool $fresh = false): array
    {
        if (! $fresh && static::$memo !== null) {
            return static::$memo;
        }

        $spec = static::loadUncached($fresh);

        return static::$memo = $spec;
    }

    /**
     * @return array
     *
     * @throws RuntimeException
     */
    protected static function loadUncached(bool $fresh = false): array
    {
        $source = static::sourcePath();
        $export = static::exportPath();

        // 1) YAML fuente (siempre que podamos parsearlo y no se fuerce el export).
        if (static::yamlParserAvailable()
            && is_readable($source)
            && ! config('docs.spec.prefer_export')) {
            $hash = (string) sha1_file($source);

            $spec = static::cached($fresh, $hash, function () use ($source) {
                return static::normalize(Yaml::parseFile($source) ?? []);
            });

            $spec[static::SOURCE_HASH_KEY] = $hash;

            return $spec;
        }

        // 2) Export JSON versionado.
        if (is_readable($export)) {
            $raw = (string) file_get_contents($export);

            return static::normalize(json_decode($raw, true, 512, JSON_THROW_ON_ERROR));
        }

        // 3) Nada utilizable: mensajes accionables.
        $hint = static::yamlParserAvailable()
            ? "Revisa que exista y sea legible el fichero {$source}."
            : "Ejecuta «composer install» (con dev) o «php artisan docs:generate» para generar ".
              "{$export}; symfony/yaml sólo está disponible en instalaciones con dev-dependencies.";

        throw new RuntimeException("No se ha podido cargar la especificación OpenAPI. {$hint}");
    }

    /**
     * Caché de proceso + caché de aplicación (best effort: si el store de caché
     * no está disponible, simplemente no se cachea).
     *
     * @param  callable():array  $callback
     */
    protected static function cached(bool $fresh, string $hash, callable $callback): array
    {
        if ($fresh || ! config('docs.cache.enabled')) {
            return $callback();
        }

        $key = sprintf('%s:%s', (string) config('docs.cache.key', 'docs.openapi'), $hash);
        $ttl = (int) config('docs.cache.ttl', 3600);

        try {
            if ($ttl > 0) {
                $cached = app('cache')->get($key);

                if (is_array($cached)) {
                    return static::normalize($cached);
                }
            }
        } catch (\Throwable) {
            return $callback();
        }

        $spec = $callback();

        try {
            if ($ttl > 0) {
                app('cache')->put($key, $spec, $ttl);
            }
        } catch (\Throwable) {
            // Silencioso: la caché es una optimización, no un requisito.
        }

        return $spec;
    }

    /**
     * Normaliza estructuras: los objetos vacíos de una spec OpenAPI no deben
     * serializarse como listas JSON.
     *
     * @param  array  $spec
     * @return array
     */
    public static function normalize(array $spec): array
    {
        array_walk_recursive($spec, function (&$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
        });

        return $spec;
    }

    /**
     * Comprueba si el export JSON versionado está desactualizado.
     *
     * @return string|null Descripción del problema, o null si está al día.
     */
    public static function exportStatus(): ?string
    {
        $export = static::exportPath();

        if (! is_readable($export)) {
            return 'El export openapi/openapi.json no existe. Ejecuta «php artisan docs:generate».';
        }

        try {
            $data = json_decode((string) file_get_contents($export), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return 'El export openapi/openapi.json no es JSON válido: '.$e->getMessage();
        }

        $hash = static::sourceHash();

        if ($hash !== null && ($data[static::SOURCE_HASH_KEY] ?? null) !== $hash) {
            return 'El export openapi/openapi.json está desactualizado respecto a openapi/openapi.yaml. '.
                'Ejecuta «php artisan docs:generate» y commitea el resultado.';
        }

        return null;
    }

    /**
     * @param  array  $spec
     */
    public static function toJson(array $spec, bool $pretty = false): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode(static::normalize($spec), $flags);
    }

    /**
     * Escribe el export JSON (con la marca de la revisión fuente).
     */
    public static function writeExport(array $spec, ?string $path = null): string
    {
        $path ??= static::exportPath();

        $hash = static::sourceHash();

        if ($hash !== null) {
            $spec[static::SOURCE_HASH_KEY] = $hash;
        }

        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, static::toJson($spec, true)."\n");

        return $path;
    }

    /**
     * Chequeos estructurales. Devuelve una lista de problemas legibles.
     *
     * @param  array  $spec
     * @return list<string>
     */
    public static function problems(array $spec): array
    {
        $problems = [];

        $version = (string) ($spec['openapi'] ?? '');

        if ($version === '') {
            $problems[] = 'Falta la clave raíz «openapi» (p. ej. 3.0.3).';
        } elseif (! str_starts_with($version, '3.')) {
            $problems[] = "Versión OpenAPI no soportada por Swagger UI 5: {$version}.";
        }

        foreach (['info.title' => 'info.title', 'info.version' => 'info.version'] as $label => $path) {
            if (static::arrayGet($spec, $path) === null) {
                $problems[] = "Falta {$label} en la sección info.";
            }
        }

        if (static::arrayGet($spec, 'servers') === null) {
            $problems[] = 'Falta la sección «servers»: Swagger UI no sabrá contra qué host probar.';
        }

        $paths = (array) ($spec['paths'] ?? []);

        if ($paths === []) {
            $problems[] = 'La sección «paths» está vacía.';
        }

        $declaredTags = [];

        foreach ((array) ($spec['tags'] ?? []) as $tag) {
            if (isset($tag['name'])) {
                $declaredTags[] = $tag['name'];
            }
        }

        $seenOperationIds = [];

        foreach ($paths as $path => $item) {
            $path = (string) $path;

            if (! is_array($item)) {
                $problems[] = "El path «{$path}» no contiene un path item válido.";

                continue;
            }

            foreach (static::METHODS as $method) {
                if (! isset($item[$method]) || ! is_array($item[$method])) {
                    continue;
                }

                $operation = $item[$method];
                $where = strtoupper($method).' '.$path;

                $operationId = $operation['operationId'] ?? null;

                if (! is_array($operation['responses'] ?? null) || ($operation['responses'] ?? []) === []) {
                    $problems[] = "{$where}: no declara ninguna respuesta.";
                }

                if (! is_string($operationId) || $operationId === '') {
                    $problems[] = "{$where}: falta el operationId (necesario para generar clients).";
                } else {
                    if (isset($seenOperationIds[$operationId])) {
                        $problems[] = "{$where}: operationId duplicado «{$operationId}».";
                    }

                    $seenOperationIds[$operationId] = true;
                }

                if (isset($operation['tags'])) {
                    foreach ((array) $operation['tags'] as $tag) {
                        if ($declaredTags !== [] && ! in_array($tag, $declaredTags, true)) {
                            $problems[] = "{$where}: tag no declarado en la sección raíz «tags»: {$tag}.";
                        }
                    }
                } else {
                    $problems[] = "{$where}: sin tag, saldrá en una sección huérfana de la UI.";
                }
            }
        }

        foreach (static::unresolvedRefs($spec) as $ref) {
            $problems[] = "Referencia sin resolver: {$ref}";
        }

        if (($spec['components']['securitySchemes'] ?? []) === []) {
            $problems[] = 'No hay securitySchemes: el botón Authorize de Swagger UI estará vacío.';
        }

        return $problems;
    }

    /**
     * Valida y lanza excepción si hay problemas y la config lo pide.
     *
     * @param  array  $spec
     * @return list<string>
     *
     * @throws RuntimeException
     */
    public static function validate(array $spec, ?bool $failOnProblems = null): array
    {
        $problems = static::problems($spec);

        $failOnProblems ??= (bool) config('docs.validation.fail_on_problems', false);

        if ($problems !== [] && $failOnProblems) {
            throw new RuntimeException("Spec OpenAPI inválida:\n - ".implode("\n - ", $problems));
        }

        return $problems;
    }

    /**
     * Recorre toda la estructura buscando `$ref` locales que no resuelven.
     *
     * @param  array  $spec
     * @return list<string>
     */
    public static function unresolvedRefs(array $spec, string $pointer = '#'): array
    {
        $found = [];

        foreach ($spec as $key => $value) {
            if ($key === '$ref' && is_string($value) && str_starts_with($value, '#/')) {
                if (static::arrayGet($spec, substr($value, 2), '/') === null) {
                    $found[] = $value.' (desde '.$pointer.')';
                }

                continue;
            }

            if (is_array($value)) {
                $found = array_merge($found, static::unresolvedRefs($value, $pointer.'/'.$key));
            }
        }

        return $found;
    }

    /**
     * @param  array  $data
     * @return mixed
     */
    public static function arrayGet(array $data, string $path, string $separator = '.')
    {
        foreach (explode($separator, $path) as $segment) {
            if (! is_array($data) || ! array_key_exists($segment, $data)) {
                return null;
            }

            $data = $data[$segment];
        }

        return $data;
    }

    /**
     * Resumen para la salida del comando y la cabecera de la UI.
     *
     * @param  array  $spec
     * @return array{paths:int,operations:int,schemas:int,tags:list<string>,security:list<string>}
     */
    public static function statistics(array $spec): array
    {
        $paths = (array) ($spec['paths'] ?? []);
        $operations = 0;

        foreach ($paths as $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach (static::METHODS as $method) {
                if (isset($item[$method]) && is_array($item[$method])) {
                    $operations++;
                }
            }
        }

        $tags = [];

        foreach ((array) ($spec['tags'] ?? []) as $tag) {
            $tags[] = is_array($tag) ? (string) ($tag['name'] ?? '') : (string) $tag;
        }

        return [
            'paths' => count($paths),
            'operations' => $operations,
            'schemas' => count((array) ($spec['components']['schemas'] ?? [])),
            'tags' => array_values(array_filter($tags, static fn (string $t): bool => $t !== '')),
            'security' => array_keys((array) ($spec['components']['securitySchemes'] ?? [])),
        ];
    }
}
