<?php
declare(strict_types=1);

namespace SpecHub\Api\Exception;

/** 403 · Token válido pero sin permisos suficientes. */
final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'No tienes permisos para realizar esta acción')
    {
        parent::__construct(403, $message);
    }
}
