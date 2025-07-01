<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Registry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegistryController extends Controller
{
    /**
     * Listar todos los registros.
     * Permite filtrar por fecha con el parámetro GET 'fecha' (ej: /api/registries?fecha=2023-10-27).
     */
    public function index(Request $request)
    {
        try {
            $query = Registry::query();

            // Para que la respuesta sea más útil, cargamos la información del Item relacionado.
            $query->with('item');

            if ($request->has('fecha')) {
                $fecha = $request->input('fecha');
                $validator = Validator::make(['fecha' => $fecha], ['fecha' => 'required|date_format:Y-m-d']);

                if ($validator->fails()) {
                    return $this->response(false, 'Formato de fecha inválido. Use AAAA-MM-DD.', $validator->errors(), 422);
                }
                
                // Filtra por el campo 'fecha_hora_ingreso' de tu modelo Registry
                $query->whereDate('fecha_hora_ingreso', $fecha);
            }

            $registries = $query->latest('fecha_hora_ingreso')->get();

            return $this->response(true, 'Lista de registros extraída correctamente', $registries);

        } catch (\Exception $e) {
            return $this->response(false, 'Error al consultar registros: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Registrar una nueva prenda (Registry) y actualizar el stock del Item.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer|exists:items,id', // El item debe existir
            'fecha_hora_ingreso' => 'required|date',
            'color' => 'required|string|max:100',
            'estado' => ['required', 'string', Rule::in(['nuevo', 'poco uso', 'usado'])],
            'precio' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->response(false, 'Error de validación', $validator->errors(), 422);
        }

        try {
            // Usamos una transacción para asegurar la integridad de los datos.
            // O se hacen ambas operaciones (crear registro y actualizar stock) o no se hace ninguna.
            $newRegistry = DB::transaction(function () use ($request) {
                // 1. Creamos el nuevo registro de la prenda
                $registry = Registry::create($request->all());

                // 2. Buscamos el Item principal y aumentamos su stock
                $item = Item::findOrFail($request->item_id);
                $item->increment('stock'); // Aumenta el stock en 1

                return $registry;
            });

            return $this->response(true, 'Prenda registrada y stock actualizado correctamente', $newRegistry->load('item'), 201);

        } catch (\Exception $e) {
            return $this->response(false, 'Error al registrar la prenda: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Mostrar un registro específico.
     */
    public function show(Registry $registry)
    {
        // Cargamos la data del item relacionado para una respuesta más completa
        return $this->response(true, 'Registro encontrado', $registry->load('item'));
    }


    /**
     * Eliminar un registro de prenda y actualizar el stock del Item.
     */
    public function destroy(Registry $registry)
    {
        try {
            // Usamos una transacción por la misma razón que en el método store.
            DB::transaction(function () use ($registry) {
                // 1. Buscamos el Item principal
                $item = Item::findOrFail($registry->item_id);

                // 2. Disminuimos el stock, asegurándonos de que no sea negativo
                if ($item->stock > 0) {
                    $item->decrement('stock'); // Disminuye el stock en 1
                }
                
                // 3. Eliminamos el registro de la prenda
                $registry->delete();
            });

            return $this->response(true, 'Registro eliminado y stock actualizado correctamente', null, 200);

        } catch (\Exception $e) {
            return $this->response(false, 'Error al eliminar el registro: ' . $e->getMessage(), null, 500);
        }
    }

    // Método helper para estandarizar las respuestas JSON
    protected function response($success, $message, $data = null, $status = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }
}