<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Estandariza las respuestas de la API.
     *
     * @param bool   $success  Indica si la operación fue exitosa.
     * @param string $message  Mensaje descriptivo.
     * @param mixed  $data     Datos a devolver (opcional).
     * @param int    $httpCode Código de estado HTTP (opcional).
     * @return \Illuminate\Http\JsonResponse
     */
    protected function response(bool $success, string $message, mixed $data = null, int $httpCode = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], $httpCode);
    }
}