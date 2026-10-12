# `openapi/` — contrato de la API

| Fichero | Rol |
|---|---|
| `openapi.yaml` | **Fuente de verdad.** Se edita a mano (OpenAPI 3.0.3). |
| `openapi.json` | Export generado por `php artisan docs:generate`. Se versiona para poder servir la doc en `composer install --no-dev`, donde no hay parser de YAML. |
| `README.md` | Este documento. |

## Reglas

1. Un PR que cambie `routes/api.php`, un `Request` de validación o un `Http\Resources\*`
   actualiza también `openapi.yaml`.
2. Después de tocar el YAML: `php artisan docs:generate` y commitea **ambos** ficheros.
   `docs:generate --check` falla si el JSON no se corresponde con el YAML
   (se compara el SHA-1 guardado en la extensión `x-source-sha1`).
3. No edites `openapi.json` a mano: se sobrescribe.
4. Las rutas declaradas pero no implementadas se documentan igual, con `x`-status de facto:
   tag **Sin implementar** y `[no implementado]` en el `summary`, para que el consumidor
   sepa que existen pero no las use.

## Estilo del YAML

- Un path por bloque, operaciones en orden `get, put, patch, post, delete`.
- `operationId` en `camelCase` y único (`tasksUpdate`, `commentsStore`, …): lo consumen los
  generadores de clients.
- Ejemplos (`example`) siempre en español/realistas: son lo que ve quien prueba el endpoint
  en Swagger UI con *Try it out*.
- Documenta el payload **real**, incluidos los dobles envoltorios `{ success, data: { data: […] } }`
  que producen los Api Resources dentro de `sendResponse()`.

## Validar fuera de la app

```bash
php artisan docs:generate --check --coverage   # cobertura + estructura
npx --yes @redocly/cli@latest lint openapi/openapi.yaml   # linteo externo (opcional)
```
