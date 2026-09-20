<?php
declare(strict_types=1);

namespace SpecHub\Api\Core;

use DateTimeImmutable;
use DateTimeZone;
use PDOException;
use SpecHub\Api\Exception\HttpException;
use Throwable;

/**
 * Traduce cualquier excepción a una respuesta JSON con la MISMA forma que
 * usa el backend Java (GlobalExceptionHandler):
 *   { timestamp, status, error, mensaje [, errores] }
 * El front-end lee "mensaje" (o "error") para mostrárselo al usuario.
 */
final class ErrorResponder
{
    private const REASONS = [
        400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found',
        405 => 'Method Not Allowed', 409 => 'Conflict', 500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    public static function from(Throwable $e, bool $debug): Response
    {
        if ($e instanceof HttpException) {
            $status = $e->status();
            $extra = $e->extra();
            $body = [
                'timestamp' => self::now(),
                'status'    => $status,
                'error'     => isset($extra['errores']) ? 'Error de validación' : (self::REASONS[$status] ?? 'Error'),
                'mensaje'   => $e->getMessage(),
            ] + $extra;
            return Response::json($body, $status, $e->headers());
        }

        // Error inesperado: se registra en el log del servidor y no se filtran detalles al cliente.
        error_log(sprintf('[SpecHub API] %s: %s en %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));

        $status = ($e instanceof PDOException && in_array((int) ($e->errorInfo[1] ?? 0), [1044, 1045, 1049, 1698, 2002], true)) ? 503 : 500;
        $body = [
            'timestamp' => self::now(),
            'status'    => $status,
            'error'     => self::REASONS[$status],
            'mensaje'   => $status === 503
                ? 'No se pudo conectar con la base de datos.'
                : 'Ocurrió un error interno en el servidor.',
        ];
        if ($debug) {
            $body['detalle'] = $e::class . ': ' . $e->getMessage();
        }
        return Response::json($body, $status);
    }

    private static function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');
    }
}
