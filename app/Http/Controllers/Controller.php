<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //método de respuesta que estandariza las respuestas de la API, mediante los atributos status, message y data, tambien
    //permite definir un código de estado HTTP personalizado
    protected function response(int $status, string $message, mixed $data = null, int $httpCode = 200)
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ], $httpCode);
    }
}
