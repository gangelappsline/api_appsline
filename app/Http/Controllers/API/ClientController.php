<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientController extends BaseController
{
    /**
     * Listado con filtros, búsqueda, orden y paginación.
     * GET /api/clients
     */
    public function index(Request $request): JsonResponse
    {
        $query = Client::query()->with(['accountManager']);

        // Búsqueda global (nombre, tax_id, email, contacto)
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('trade_name', 'like', "%{$search}%")
                  ->orWhere('tax_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        // Filtros
        $query->when($request->filled('status'),
            fn ($q) => $q->where('status', $request->input('status')));

        $query->when($request->filled('client_type'),
            fn ($q) => $q->where('client_type', $request->input('client_type')));

        $query->when($request->filled('country'),
            fn ($q) => $q->where('country', $request->input('country')));

        $query->when($request->filled('account_manager_id'),
            fn ($q) => $q->where('account_manager_id', $request->input('account_manager_id')));

        // Rango de fechas de alta
        $query->when($request->filled('from'),
            fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')));

        $query->when($request->filled('to'),
            fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')));

        // ¿Incluir eliminados?
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        } elseif ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        }

        // Conteo de proyectos (opcional)
        if ($request->boolean('with_projects_count')) {
            $query->withCount('projects');
        }

        // Ordenamiento
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['business_name', 'tax_id', 'status', 'created_at', 'onboarded_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $clients = $query->paginate($perPage)->appends($request->query());

        return $this->sendResponse(ClientResource::collection($clients), "Clientes obtenidos correctamente.");
    }

    /**
     * Guardar un cliente nuevo.
     * POST /api/clients
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        try {
            $client = DB::transaction(function () use ($request) {
                return Client::create($request->validated());
            });

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado correctamente.',
                'data'    => new ClientResource($client->load('accountManager')),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Error al crear cliente', [
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el cliente.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Mostrar un cliente.
     * GET /api/clients/{client}
     */
    public function show(Request $request, Client $client): JsonResponse
    {
        $with = ['accountManager'];

        if ($request->boolean('with_projects')) {
            $with[] = 'projects';
        }

        return response()->json([
            'success' => true,
            'data'    => new ClientResource($client->load($with)),
        ]);
    }

    /**
     * Actualizar un cliente.
     * PUT/PATCH /api/clients/{client}
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $client) {
                $client->update($request->validated());
            });

            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado correctamente.',
                'data'    => new ClientResource($client->fresh()->load('accountManager')),
            ]);

        } catch (\Throwable $e) {
            Log::error('Error al actualizar cliente', [
                'client_id' => $client->id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el cliente.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Eliminar (soft delete) un cliente.
     * DELETE /api/clients/{client}
     */
    public function destroy(Client $client): JsonResponse
    {
        // Opcional: impedir borrar si tiene proyectos activos
        if ($client->projects()->whereIn('status', ['in_progress', 'planning'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: el cliente tiene proyectos activos.',
            ], 409);
        }

        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado correctamente.',
        ]);
    }

    /**
     * Restaurar un cliente eliminado.
     * POST /api/clients/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        $client = Client::onlyTrashed()->findOrFail($id);
        $client->restore();

        return response()->json([
            'success' => true,
            'message' => 'Cliente restaurado correctamente.',
            'data'    => new ClientResource($client->load('accountManager')),
        ]);
    }

    /**
     * Cambio rápido de estado (útil para toggles en el front).
     * PATCH /api/clients/{client}/status
     */
    public function updateStatus(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive,prospect,suspended'],
        ]);

        $client->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado.',
            'data'    => new ClientResource($client),
        ]);
    }

    /**
     * Listado ligero para selects/autocompletar.
     * GET /api/clients/options
     */
    public function options(Request $request): JsonResponse
    {
        $clients = Client::query()
            ->select('id', 'business_name', 'tax_id')
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('business_name', 'like', "%{$s}%")
                  ->orWhere('tax_id', 'like', "%{$s}%");
            })
            ->orderBy('business_name')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $clients,
        ]);
    }
}