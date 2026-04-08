<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
// 1. Agregamos estas dos clases necesarias para manejar el error
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        // 2. Interceptamos el error de autenticación
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            // Si la petición viene de nuestras rutas /api/...
            if ($request->is('api/*')) {
                // Devolvemos un error 401 limpio en formato JSON
                return response()->json([
                    'message' => 'No autenticado. Por favor inicia sesión.'
                ], 401);
            }
        });

    })->create();