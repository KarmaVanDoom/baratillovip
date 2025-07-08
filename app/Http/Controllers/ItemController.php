<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Registry;
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
            // ANTES: $this->response(1, 'Lista de items extraída correctamente', $items);
            // AHORA:
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
            'tipo' => 'required|string|in:polera,pantalón,camisa,chaqueta,falda,vestido,zapato,zapatilla', // Mejorado para usar la lista del enum
            'talla' => 'required|integer', // Talla debería ser integer
            'stock' => 'required|integer|min:0',
        ]); 

        if ($validator->fails()) {
            return $this->response(false, 'Error de validación', $validator->errors(), 422);
        }

        try {
            $newItem = item::create($validator->validated());
            // ANTES: $this->response(1, 'item creado', $newItem, 201);
            // AHORA:
            return $this->response(true, 'Item creado correctamente', $newItem, 201);
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
            // ANTES: $this->response(1, 'item encontrado', $item);
            // AHORA:
            return $this->response(true, 'Item encontrado', $item);
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
            'tipo' => 'sometimes|required|string|in:polera,pantalón,camisa,chaqueta,falda,vestido,zapato,zapatilla', // Mejorado
            'talla' => 'sometimes|required|integer', // Mejorado
            'stock' => 'sometimes|required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->response(false, 'Error de validación', $validator->errors(), 422);
        }

        try {
            $item->update($validator->validated());
            // ANTES: $this->response(1, 'item actualizado', $item);
            // AHORA:
            return $this->response(true, 'Item actualizado correctamente', $item);
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

        if ($item->stock > 0) {
            return $this->response(false, 'No se puede eliminar una prenda que tiene stock.', null, 409);
        }

        try {
            $item->delete();
            // ANTES: $this->response(1, 'item eliminado correctamente', null);
            // AHORA:
            return $this->response(true, 'Item eliminado correctamente 1111', null, 200);
        } catch (\Exception $e) {
            return $this->response(false, 'Error al eliminar itemmmmm : ' . $e->getMessage(), null, 500);
        }
    }

    public function inventory()
    {
        try {
            $items = Item::withCount('registries')->get();

            $inventory = $items->map(function ($item) {
                return [
                    'marca' => $item->marca,
                    'tipo' => $item->tipo,
                    'talla' => $item->talla,
                    'stock_disponible' => $item->stock,
                    'registros_totales' => $item->registries_count,
                ];
            });

            // ANTES: $this->response(1, 'Inventario extraído correctamente', $inventory);
            // AHORA:
            return $this->response(true, 'Inventario extraído correctamente', $inventory);

        } catch (\Exception $e) {
            return $this->response(false, 'Error al consultar el inventario: ' . $e->getMessage(), null, 500);
        }
    }
}