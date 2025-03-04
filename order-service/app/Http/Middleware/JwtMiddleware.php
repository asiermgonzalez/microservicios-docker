<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{

    /**
     * Obtener el token de la petición y verificarlo
     * Asier Martín
     * 04-03-2025
     * 
     */
    public function handle(Request $request, Closure $next): Response
    {

        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        try {
            $token = str_replace('Bearer ', '', $token);

            // Instalar firebase/php-jwt para tener acceso a los siguientes métodos
            JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

            return $next($request);
        } catch (Exception $e) {
            return response()->json(['error' => 'Token invalid'. $e->getMessage()], HttpResponse::HTTP_UNAUTHORIZED);
        }
    }
}
