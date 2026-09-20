<?php
declare(strict_types=1);

namespace SpecHub\Api\Middleware;

use SpecHub\Api\Core\Request;
use SpecHub\Api\Core\Response;
use SpecHub\Api\Exception\ForbiddenException;
use SpecHub\Api\Exception\UnauthorizedException;
use SpecHub\Api\Service\JwtService;

/**
 * Protege las rutas de escritura (POST / PUT / DELETE): exige la cabecera
 *   Authorization: Bearer <token>
 * emitida por POST /api/auth/login, con rol ADMIN.
 *  - sin token / token inválido o vencido -> 401
 *  - token válido pero sin rol ADMIN      -> 403
 */
final class AuthMiddleware implements Middleware
{
    public function __construct(private JwtService $jwt)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $header = $request->header('authorization');
        if ($header === null || !preg_match('/^Bearer\s+(\S+)$/i', trim($header), $m)) {
            throw new UnauthorizedException('Se requiere la cabecera Authorization: Bearer <token>');
        }

        $claims = $this->jwt->validar($m[1]);

        if (($claims['role'] ?? null) !== 'ADMIN') {
            throw new ForbiddenException('Se requiere rol ADMIN para esta operación');
        }
        $request->attributes['user'] = (string) $claims['sub'];

        return $next($request);
    }
}
