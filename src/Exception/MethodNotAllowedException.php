<?php
declare(strict_types=1);

namespace SpecHub\Api\Exception;

/** 405 · La ruta existe pero no admite ese método HTTP (incluye cabecera Allow). */
final class MethodNotAllowedException extends HttpException
{
    /** @param list<string> $allowed */
    public function __construct(array $allowed)
    {
        $allow = implode(', ', $allowed);
        parent::__construct(405, "Método no permitido para esta ruta. Métodos admitidos: $allow", [], ['Allow' => $allow]);
    }
}
