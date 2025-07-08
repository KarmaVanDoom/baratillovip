<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Registry;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Listar todos los items....
            $items = Item::all();
            return response()->json(['items' => $items], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar el inventario: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validaciones del servidor....
            $request->validate([
                'marca' => 'required|string|max:255|min:1',
                'tipo' => 'required|in:polera,pantalon,camisa,chaqueta,falda,vestido,zapato,zapatilla',
                'talla' => 'required|integer|min:1|max:100',
                'stock' => 'required|integer|min:0|max:9999'
            ], [
                'marca.required' => 'La marca es requerida',
                'marca.string' => 'La marca debe ser texto',
                'marca.max' => 'La marca no puede tener más de 255 caracteres',
                'marca.min' => 'La marca no puede estar vacía',
                'tipo.required' => 'El tipo es requerido',
                'tipo.in' => 'El tipo debe ser: polera, pantalón, camisa, chaqueta, falda, vestido, zapato o zapatilla',
                'talla.required' => 'La talla es requerida',
                'talla.integer' => 'La talla debe ser un número entero',
                'talla.min' => 'La talla debe ser mayor a 0',
                'talla.max' => 'La talla no puede ser mayor a 100',
                'stock.required' => 'El stock es requerido',
                'stock.integer' => 'El stock debe ser un número entero',
                'stock.min' => 'El stock no puede ser negativo',
                'stock.max' => 'El stock no puede ser mayor a 9999'
            ]);

            // Validar duplicados (misma marca, tipo y talla)
            $itemExistente = Item::where('marca', $request->marca)
                                 ->where('tipo', $request->tipo)
                                 ->where('talla', $request->talla)
                                 ->first();

            if ($itemExistente) {
                return response()->json([
                    'error' => 'Ya existe una prenda con la misma marca, tipo y talla'
                ], 422);
            }

            // Limpiar y formatear datos
            $marca = trim($request->marca);
            $tipo = strtolower(trim($request->tipo));
            $talla = (int) $request->talla;
            $stock = (int) $request->stock;

            $newItem = Item::create([
                'marca' => $marca,
                'tipo' => $tipo,
                'talla' => $talla,
                'stock' => $stock
            ]);

            return response()->json([
                'message' => 'Prenda agregada exitosamente',
                'item' => $newItem
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Datos inválidos',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al guardar la prenda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['error' => 'ID inválido'], 400);
            }

            $item = Item::find($id);

            if (!$item) {
                return response()->json(['error' => 'Prenda no encontrada'], 404);
            }

            return response()->json(['item' => $item], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener la prenda: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['error' => 'ID inválido'], 400);
            }

            $item = Item::find($id);

            if (!$item) {
                return response()->json(['error' => 'Prenda no encontrada'], 404);
            }

            // Validaciones del servidor....
            $request->validate([
                'marca' => 'required|string|max:255|min:1',
                'tipo' => 'required|in:polera,pantalon,camisa,chaqueta,falda,vestido,zapato,zapatilla',
                'talla' => 'required|integer|min:1|max:100',
                'stock' => 'required|integer|min:0|max:9999'
            ], [
                'marca.required' => 'La marca es requerida',
                'marca.string' => 'La marca debe ser texto',
                'marca.max' => 'La marca no puede tener más de 255 caracteres',
                'marca.min' => 'La marca no puede estar vacía',
                'tipo.required' => 'El tipo es requerido',
                'tipo.in' => 'El tipo debe ser: polera, pantalón, camisa, chaqueta, falda, vestido, zapato o zapatilla',
                'talla.required' => 'La talla es requerida',
                'talla.integer' => 'La talla debe ser un número entero',
                'talla.min' => 'La talla debe ser mayor a 0',
                'talla.max' => 'La talla no puede ser mayor a 100',
                'stock.required' => 'El stock es requerido',
                'stock.integer' => 'El stock debe ser un número entero',
                'stock.min' => 'El stock no puede ser negativo',
                'stock.max' => 'El stock no puede ser mayor a 9999'
            ]);

            // Validar duplicados (excluyendo el item actual)
            $itemExistente = Item::where('marca', $request->marca)
                                 ->where('tipo', $request->tipo)
                                 ->where('talla', $request->talla)
                                 ->where('id', '!=', $id)
                                 ->first();

            if ($itemExistente) {
                return response()->json([
                    'error' => 'Ya existe otra prenda con la misma marca, tipo y talla'
                ], 422);
            }

            // Validar que el stock no sea menor que los registros existentes
            $registrosCount = Registry::where('item_id', $id)->count();
            if ($request->stock < $registrosCount) {
                return response()->json([
                    'error' => "El stock no puede ser menor a {$registrosCount} (registros existentes)"
                ], 422);
            }

            // Limpiar y formatear datos
            $marca = trim($request->marca);
            $tipo = strtolower(trim($request->tipo));
            $talla = (int) $request->talla;
            $stock = (int) $request->stock;

            // Actualizar los campos del item....
            $item->marca = $marca;
            $item->tipo = $tipo;
            $item->talla = $talla;
            $item->stock = $stock;
            $item->save();

            return response()->json([
                'message' => 'Prenda actualizada exitosamente',
                'item' => $item
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Datos inválidos',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar la prenda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['error' => 'ID inválido'], 400);
            }

            $item = Item::find($id);

            if (!$item) {
                return response()->json(['error' => 'Prenda no encontrada'], 404);
            }

            // Validar que no tenga stock
            if ($item->stock > 0) {
                return response()->json([
                    'error' => 'No se puede eliminar una prenda con stock. Stock actual: ' . $item->stock
                ], 422);
            }

            // Validar que no tenga registros asociados
            $registrosCount = Registry::where('item_id', $id)->count();
            if ($registrosCount > 0) {
                return response()->json([
                    'error' => "No se puede eliminar la prenda porque tiene {$registrosCount} registros asociados"
                ], 422);
            }

            $itemEliminado = $item->toArray(); // Guardar datos antes de eliminar
            $item->delete();
            
            return response()->json([
                'message' => 'Prenda eliminada exitosamente',
                'item' => $itemEliminado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar la prenda: ' . $e->getMessage()
            ], 500);
        }
    }
}