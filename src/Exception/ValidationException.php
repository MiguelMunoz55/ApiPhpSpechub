<?php
declare(strict_types=1);

namespace SpecHub\Api\Exception;

/** 400 · El cuerpo JSON es válido pero sus campos no cumplen las reglas. */
final class ValidationException extends HttpException
{
    /** @param array<string,string> $errores campo => mensaje */
    public function __construct(array $errores)
    {
        parent::__construct(400, 'La petición contiene datos inválidos', ['errores' => $errores]);
    }
}
