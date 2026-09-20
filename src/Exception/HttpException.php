<?php
declare(strict_types=1);

namespace SpecHub\Api\Exception;

use RuntimeException;
use Throwable;

/**
 * Base de todos los errores "esperados" de la API. Cada subclase fija un código
 * HTTP; el ErrorResponder los traduce a una respuesta JSON uniforme.
 */
class HttpException extends RuntimeException
{
    /**
     * @param array<string,mixed>  $extra   campos adicionales para el cuerpo JSON
     * @param array<string,string> $headers cabeceras HTTP adicionales
     */
    public function __construct(
        private int $status,
        string $message,
        private array $extra = [],
        private array $headers = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string,mixed> */
    public function extra(): array
    {
        return $this->extra;
    }

    /** @return array<string,string> */
    public function headers(): array
    {
        return $this->headers;
    }
}
