<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Registry;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class RegistryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Listar todos los registros con información de items....
            $registries = Registry::with('item')->orderBy('fecha_hora_ingreso', 'desc')->get();

            return response()->json(['registries' => $registries], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar los registros: ' . $e->getMessage()], 500);
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
                'item_id' => 'required|integer|exists:items,id',
                'fecha_hora_ingreso' => 'sometimes|date|before_or_equal:now',
                'color' => 'required|string|max:100|min:1',
                'estado' => 'required|in:nuevo,poco uso,usado',
                'precio' => 'required|numeric|min:0|max:999999.99'
            ], [
                'item_id.required' => 'Debe seleccionar una prenda',
                'item_id.integer' => 'ID de prenda inválido',
                'item_id.exists' => 'La prenda seleccionada no existe',
                'fecha_hora_ingreso.date' => 'Fecha y hora inválidas',
                'fecha_hora_ingreso.before_or_equal' => 'La fecha no puede ser futura',
                'color.required' => 'El color es requerido',
                'color.string' => 'El color debe ser texto',
                'color.max' => 'El color no puede tener más de 100 caracteres',
                'color.min' => 'El color no puede estar vacío',
                'estado.required' => 'El estado es requerido',
                'estado.in' => 'El estado debe ser: nuevo, poco uso o usado',
                'precio.required' => 'El precio es requerido',
                'precio.numeric' => 'El precio debe ser un número',
                'precio.min' => 'El precio no puede ser negativo',
                'precio.max' => 'El precio no puede ser mayor a 999,999.99'
            ]);

            // Verificar que el item existe y obtener información
            $item = Item::find($request->item_id);
            if (!$item) {
                return response()->json(['error' => 'La prenda seleccionada no existe'], 404);
            }

            // Validar que haya stock disponible
            if ($item->stock <= 0) {
                return response()->json([
                    'error' => 'No hay stock disponible para esta prenda. Stock actual: ' . $item->stock
                ], 422);
            }

            // Limpiar y formatear datos
            $color = trim($request->color);
            $estado = strtolower(trim($request->estado));
            $precio = round((float) $request->precio, 2);

            // Usar fecha actual si no se proporciona
            $fechaHora = $request->has('fecha_hora_ingreso') 
                ? $request->fecha_hora_ingreso 
                : now();

            // Validar que la fecha no sea muy antigua (más de 1 año)
            if (Carbon::parse($fechaHora)->lt(Carbon::now()->subYear())) {
                return response()->json([
                    'error' => 'La fecha no puede ser anterior a un año'
                ], 422);
            }

            // Iniciar transacción para consistencia
            DB::beginTransaction();

            try {
                // Crear el registro
                $newRegistry = Registry::create([
                    'item_id' => $request->item_id,
                    'fecha_hora_ingreso' => $fechaHora,
                    'color' => $color,
                    'estado' => $estado,
                    'precio' => $precio
                ]);

                // Reducir stock del item
                $item->stock = $item->stock - 1;
                $item->save();

                // Confirmar transacción
                DB::commit();

                // Recargar el registro con la relación
                $newRegistry->load('item');

                return response()->json([
                    'message' => 'Registro creado exitosamente',
                    'registry' => $newRegistry
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Datos inválidos',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear el registro: ' . $e->getMessage()
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

            $registry = Registry::with('item')->find($id);

            if (!$registry) {
                return response()->json(['error' => 'Registro no encontrado'], 404);
            }

            return response()->json(['registry' => $registry], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener el registro: ' . $e->getMessage()], 500);
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

            $registry = Registry::find($id);

            if (!$registry) {
                return response()->json(['error' => 'Registro no encontrado'], 404);
            }

            // Validaciones del servidor....
            $request->validate([
                'item_id' => 'required|integer|exists:items,id',
                'fecha_hora_ingreso' => 'required|date|before_or_equal:now',
                'color' => 'required|string|max:100|min:1',
                'estado' => 'required|in:nuevo,poco uso,usado',
                'precio' => 'required|numeric|min:0|max:999999.99'
            ], [
                'item_id.required' => 'Debe seleccionar una prenda',
                'item_id.integer' => 'ID de prenda inválido',
                'item_id.exists' => 'La prenda seleccionada no existe',
                'fecha_hora_ingreso.required' => 'La fecha y hora son requeridas',
                'fecha_hora_ingreso.date' => 'Fecha y hora inválidas',
                'fecha_hora_ingreso.before_or_equal' => 'La fecha no puede ser futura',
                'color.required' => 'El color es requerido',
                'color.string' => 'El color debe ser texto',
                'color.max' => 'El color no puede tener más de 100 caracteres',
                'color.min' => 'El color no puede estar vacío',
                'estado.required' => 'El estado es requerido',
                'estado.in' => 'El estado debe ser: nuevo, poco uso o usado',
                'precio.required' => 'El precio es requerido',
                'precio.numeric' => 'El precio debe ser un número',
                'precio.min' => 'El precio no puede ser negativo',
                'precio.max' => 'El precio no puede ser mayor a 999,999.99'
            ]);

            // Verificar que el item existe
            $item = Item::find($request->item_id);
            if (!$item) {
                return response()->json(['error' => 'La prenda seleccionada no existe'], 404);
            }

            // Validar que la fecha no sea muy antigua
            if (Carbon::parse($request->fecha_hora_ingreso)->lt(Carbon::now()->subYear())) {
                return response()->json([
                    'error' => 'La fecha no puede ser anterior a un año'
                ], 422);
            }

            // Iniciar transacción para consistencia
            DB::beginTransaction();

            try {
                $itemIdAnterior = $registry->item_id;
                $itemIdNuevo = $request->item_id;

                // Si cambió el item, actualizar stocks
                if ($itemIdAnterior != $itemIdNuevo) {
                    // Aumentar stock del item anterior
                    $itemAnterior = Item::find($itemIdAnterior);
                    if ($itemAnterior) {
                        $itemAnterior->stock = $itemAnterior->stock + 1;
                        $itemAnterior->save();
                    }

                    // Verificar stock del nuevo item
                    if ($item->stock <= 0) {
                        throw new \Exception('No hay stock disponible en la nueva prenda seleccionada');
                    }

                    // Reducir stock del nuevo item
                    $item->stock = $item->stock - 1;
                    $item->save();
                }

                // Limpiar y formatear datos
                $color = trim($request->color);
                $estado = strtolower(trim($request->estado));
                $precio = round((float) $request->precio, 2);

                // Actualizar los campos del registro....
                $registry->item_id = $itemIdNuevo;
                $registry->fecha_hora_ingreso = $request->fecha_hora_ingreso;
                $registry->color = $color;
                $registry->estado = $estado;
                $registry->precio = $precio;
                $registry->save();

                // Confirmar transacción
                DB::commit();

                // Recargar el registro con la relación
                $registry->load('item');
                
                return response()->json([
                    'message' => 'Registro actualizado exitosamente',
                    'registry' => $registry
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Datos inválidos',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el registro: ' . $e->getMessage()
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

            $registry = Registry::find($id);

            if (!$registry) {
                return response()->json(['error' => 'Registro no encontrado'], 404);
            }

            // Obtener el item relacionado
            $item = Item::find($registry->item_id);
            if (!$item) {
                return response()->json(['error' => 'La prenda relacionada no existe'], 404);
            }

            // Iniciar transacción para consistencia
            DB::beginTransaction();

            try {
                $registryEliminado = $registry->toArray(); // Guardar datos antes de eliminar

                // Eliminar el registro
                $registry->delete();

                // Aumentar stock del item (devolver al inventario)
                $item->stock = $item->stock + 1;
                $item->save();

                // Confirmar transacción
                DB::commit();
                
                return response()->json([
                    'message' => 'Registro eliminado exitosamente',
                    'registry' => $registryEliminado
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar el registro: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener registros filtrados por fecha
     */
    public function filtrarPorFecha(Request $request)
    {
        try {
            $request->validate([
                'fecha' => 'required|date'
            ]);

            $fecha = $request->fecha;
            $registries = Registry::with('item')
                ->whereDate('fecha_hora_ingreso', $fecha)
                ->orderBy('fecha_hora_ingreso', 'desc')
                ->get();

            return response()->json([
                'registries' => $registries,
                'fecha' => $fecha,
                'total' => $registries->count()
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Fecha inválida',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al filtrar registros: ' . $e->getMessage()
            ], 500);
        }
    }
}