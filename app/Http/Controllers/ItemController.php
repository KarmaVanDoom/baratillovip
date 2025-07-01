<?php

namespace App\Http\Controllers;

use App\Models\item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{

            $items = item::all();
              return $this->response(true, 'Lista de items extraída correctamente', $items);
        } catch (\Exception $e) {
            return $this->response(false, 'Error al consultar items: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marca' => 'required|string|max:255',
            'tipo' => 'required|string|max:255',
            'talla' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
        ]); 

        // si tengo un error de validación, retorna un error 422
        if ($validator->fails()) {
            return $this->response(false, 'Error de validación ', $validator->errors(), 422);
        }
        // Crea el item en base a los parámetros que devuelve la validación
        $item = $validator->validated();

        try {
            // Crear un nuevo item
            $newitem = item::create($item);

            return $this->response(true, 'item creado ', $newitem, 201);
        } catch (\Exception $e) {
            return $this->response(false, 'Error al crear item: ' . $e->getMessage(), null, 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(item $item)
    {
        try {
            // Retorna el item solicitado
            return $this->response(true, 'item encontrado', $item);
        } catch (\Exception $e) {
            return $this->response(false, 'Error al consultar item: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, item $item)
    {
        $validator = Validator::make($request->all(), [
            'marca' => 'sometimes|required|string|max:255',
            'tipo' => 'sometimes|required|string|max:255',
            'talla' => 'sometimes|required|string|max:50',
            'stock' => 'sometimes|required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->response(false, 'Error de validación ', $validator->errors(), 422);
        }

        try {
            // Actualiza el item con los datos validados
            $item->update($validator->validated());

            return $this->response(true, 'item actualizado', $item);
        } catch (\Exception $e) {
            return $this->response(false, 'Error al actualizar item: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(?item $item)
    {
        if (!$item) {
            return $this->response(false, 'Item no encontrado', null, 404);
        }

        try {
            // Elimina el item
            $item->delete();

            return $this->response(true, 'item eliminado correctamente', null, 200);
        } catch (\Exception $e) {
            return $this->response(false, 'Error al eliminar item: ' . $e->getMessage(), null, 500);
        }

        // Validación de seguridad: no permitir eliminar si hay stock.
        if ($item->stock > 0) {
            return $this->response(false, 'No se puede eliminar una prenda que tiene stock.', null, 409);
        }

        try {
            $item->delete();
            return $this->response(true, 'Prenda eliminada correctamente', null, 200);
        } catch (\Exception $e) {
            return $this->response(false, 'Error al eliminar la prenda: ' . $e->getMessage(), null, 500);
        }

    }

    public function inventory()
    {
        try {
            // Usamos withCount para que Eloquent cuente eficientemente los registros de cada item.
            // Esto es mucho más rápido que hacer un bucle.
            $items = Item::withCount('registries')->get();

            // Transformamos los datos para que coincidan con lo que el frontend espera
            $inventory = $items->map(function ($item) {
                return [
                    'marca' => $item->marca,
                    'tipo' => $item->tipo,
                    'talla' => $item->talla,
                    'stock_disponible' => $item->stock, // El JS espera 'stock_disponible'
                    'registros_totales' => $item->registries_count, // El JS espera 'registros_totales'
                ];
            });

            return $this->response(true, 'Inventario extraído correctamente', $inventory);

        } catch (\Exception $e) {
            return $this->response(false, 'Error al consultar el inventario: ' . $e->getMessage(), null, 500);
        }
    }
}
