<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\RegistryController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// RUTAS PARA LOS ITEMS....

// Ruta para listar todos los items
Route::get('items', [ItemController::class, 'index']);

// Ruta para obtener un item específico
Route::get('items/{id}', [ItemController::class, 'show']);

// Ruta para crear items
Route::post('items', [ItemController::class, 'store']);

// Ruta para actualizar items
Route::put('items/{id}', [ItemController::class, 'update']);

// Ruta para eliminar items
Route::delete('items/{id}', [ItemController::class, 'destroy']);

// RUTAS PARA LOS REGISTROS....

// Ruta para listar todos los registros
Route::get('registries', [RegistryController::class, 'index']);

// Ruta para obtener un registro específico
Route::get('registries/{id}', [RegistryController::class, 'show']);

// Ruta para crear registros
Route::post('registries', [RegistryController::class, 'store']);

// Ruta para actualizar registros
Route::put('registries/{id}', [RegistryController::class, 'update']);

// Ruta para eliminar registros
Route::delete('registries/{id}', [RegistryController::class, 'destroy']);

// Ruta para filtrar registros por fecha
Route::get('registries/filter/fecha', [RegistryController::class, 'filtrarPorFecha']);

// RUTAS COMPATIBLES CON EL FRONTEND EXISTENTE (por si acaso)....

// Rutas alternativas para mantener compatibilidad
Route::get('items/index', [ItemController::class, 'index']);
Route::post('items/store', [ItemController::class, 'store']);
Route::get('registries/index', [RegistryController::class, 'index']);
Route::post('registries/store', [RegistryController::class, 'store']);