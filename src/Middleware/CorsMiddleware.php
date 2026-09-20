<?php
declare(strict_types=1);

namespace SpecHub\Api\Middleware;

use SpecHub\Api\Core\Env;
use SpecHub\Api\Core\Request;
use SpecHub\Api\Core\Response;

/**
 * CORS: permite que el front-end React (Vite, http://localhost:5173) llame a la API
 * desde el navegador. Se aplica a TODAS las respuestas (incluidos los errores).
 * Mismos orígenes que el SecurityConfig de Java: localhost y 127.0.0.1 en cualquier puerto.
 */
final class CorsMiddleware
{
    public function aplicar(Request $request, Response $response): Response
    {
        $origin = $request->header('origin');
        if ($origin === null || !$this->permitido($origin)) {
            return $response;
        }
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Max-Age', '3600')
            ->withHeader('Vary', 'Origin');
    }

    private function permitido(string $origin): bool
    {
        if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin)) {
            return true;
        }
        $extra = array_filter(array_map('trim', explode(',', Env::get('CORS_ALLOWED_ORIGINS', '') ?? '')));
        return in_array($origin, $extra, true);
    }
}
