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
     */
    public function index(Request $request)
    {
        try {
            $query = Registry::query()->with('item');

            if ($request->has('fecha')) {
                // He corregido el formato de fecha para que coincida con el estándar de Laravel.
                // El frontend ya envía Y-m-d H:i:s, pero es bueno tener una validación robusta.
                $validator = Validator::make($request->all(), ['fecha' => 'required|date_format:Y-m-d']);

                if ($validator->fails()) {
                    return $this->response(false, 'Formato de fecha inválido. Use AAAA-MM-DD.', $validator->errors(), 422);
                }
                
                $query->whereDate('fecha_hora_ingreso', $request->input('fecha'));
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
            'item_id' => 'required|integer|exists:items,id',
            'fecha_hora_ingreso' => 'required|date',
            'color' => 'required|string|max:100',
            'estado' => ['required', 'string', Rule::in(['nuevo', 'poco uso', 'usado'])],
            'precio' => 'required|integer|min:0', 
        ]);

        if ($validator->fails()) {
            return $this->response(false, 'Error de validación', $validator->errors(), 422);
        }

        try {
            $newRegistry = DB::transaction(function () use ($request) {
                $registry = Registry::create($request->all());
                $item = Item::findOrFail($request->item_id);
                $item->increment('stock');
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
        return $this->response(true, 'Registro encontrado', $registry->load('item'));
    }


    /**
     * Eliminar un registro de prenda y actualizar el stock del Item.
     */
    public function destroy(Registry $registry)
    {
        try {
            DB::transaction(function () use ($registry) {
                $item = Item::findOrFail($registry->item_id);
                if ($item->stock > 0) {
                    $item->decrement('stock');
                }
                $registry->delete();
            });

            return $this->response(true, 'Registro eliminado y stock actualizado correctamente', null, 200);

        } catch (\Exception $e) {
            return $this->response(false, 'Error al eliminar el registro: ' . $e->getMessage(), null, 500);
        }
    }

    // ELIMINAMOS ESTE MÉTODO PORQUE AHORA SE HEREDA DEL CONTROLLER BASE
    /*
    protected function response($success, $message, $data = null, $status = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }
    */
}