<?php
declare(strict_types=1);

namespace SpecHub\Api\Exception;

/** 401 · Falta el token JWT, es inválido o expiró. */
final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Autenticación requerida')
    {
        parent::__construct(401, $message, [], ['WWW-Authenticate' => 'Bearer']);
    }
}
