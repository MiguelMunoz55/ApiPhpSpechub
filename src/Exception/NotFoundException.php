<?php
declare(strict_types=1);

namespace SpecHub\Api\Exception;

/** 404 · El recurso solicitado no existe. */
final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Recurso no encontrado')
    {
        parent::__construct(404, $message);
    }
}
