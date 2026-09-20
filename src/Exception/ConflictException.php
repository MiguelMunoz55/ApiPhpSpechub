<?php
declare(strict_types=1);

namespace SpecHub\Api\Exception;

/** 409 · La operación choca con el estado actual (duplicados, integridad referencial). */
final class ConflictException extends HttpException
{
    public function __construct(string $message = 'Conflicto con el estado actual del recurso')
    {
        parent::__construct(409, $message);
    }
}
