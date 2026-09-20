<?php
declare(strict_types=1);

namespace SpecHub\Api\Exception;

/** 400 · Petición mal formada (JSON inválido, parámetro con formato incorrecto). */
final class BadRequestException extends HttpException
{
    public function __construct(string $message = 'Petición inválida')
    {
        parent::__construct(400, $message);
    }
}
