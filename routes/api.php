<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\RegistryController;

Route::apiResource('prendas', ItemController::class);

Route::apiResource('registros', RegistryController::class);

//ruta personalizada para obtener el inventario de prendas
Route::get('/inventario', [ItemController::class, 'inventory']);