<?php
declare(strict_types=1);

namespace SpecHub\Api\Core;

use SpecHub\Api\Exception\BadRequestException;

/**
 * Representa la petición HTTP entrante. Encapsula $_SERVER / php://input para
 * que ninguna otra capa toque variables globales.
 */
final class Request
{
    /** @var array<string,string> parámetros de ruta, p. ej. ['id' => '5'] */
    public array $params = [];
    /** @var array<string,mixed> datos que los middlewares dejan para el controlador */
    public array $attributes = [];

    /**
     * @param array<string,mixed>  $query
     * @param array<string,string> $headers nombres en minúscula
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $basePath,
        public readonly array $query,
        public readonly array $headers,
        private readonly string $rawBody
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = '/' . trim(rawurldecode($uri), '/');

        // La API siempre cuelga de "/api". Todo lo que haya antes (p. ej. una
        // subcarpeta de XAMPP: /spechub/backend-php/public) es el "basePath".
        $basePath = '';
        $pos = strpos($uri . '/', '/api/');
        if ($pos !== false && $pos > 0) {
            $basePath = substr($uri, 0, $pos);
            $uri = substr($uri, $pos);
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }
        // Apache + CGI/FPM a veces renombra Authorization
        if (!isset($headers['authorization']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return new self($method, $uri, $basePath, $_GET, $headers, (string) file_get_contents('php://input'));
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Cuerpo de la petición decodificado como objeto JSON.
     *
     * @return array<string,mixed>
     */
    public function jsonBody(): array
    {
        $raw = trim($this->rawBody);
        if ($raw === '') {
            throw new BadRequestException('El cuerpo de la petición está vacío: se esperaba un objeto JSON.');
        }
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestException('El cuerpo de la petición no es un JSON válido: ' . json_last_error_msg() . '.');
        }
        if (!is_array($data) || ($data !== [] && array_is_list($data))) {
            throw new BadRequestException('El cuerpo de la petición debe ser un objeto JSON ({ ... }).');
        }
        return $data;
    }

    /** Parámetro de ruta entero positivo (p. ej. {id}); 400 si no lo es. */
    public function intParam(string $name): int
    {
        $value = $this->params[$name] ?? '';
        if (!ctype_digit($value) || strlen($value) > 18 || (int) $value < 1) {
            throw new BadRequestException("El parámetro '$name' debe ser un entero positivo (recibido: '$value').");
        }
        return (int) $value;
    }

    /** Query string como texto recortado; null si no viene o está vacío. */
    public function queryString(string $name): ?string
    {
        $value = $this->query[$name] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new BadRequestException("El parámetro '$name' debe ser un único valor de texto.");
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    public function queryInt(string $name): ?int
    {
        $value = $this->queryString($name);
        if ($value === null) {
            return null;
        }
        if (!ctype_digit($value) || strlen($value) > 18 || (int) $value < 1) {
            throw new BadRequestException("El parámetro '$name' debe ser un entero positivo (recibido: '$value').");
        }
        return (int) $value;
    }

    /** Devuelve el decimal como string normalizado (evita errores de coma flotante). */
    public function queryDecimal(string $name): ?string
    {
        $value = $this->queryString($name);
        if ($value === null) {
            return null;
        }
        if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $value)) {
            throw new BadRequestException("El parámetro '$name' debe ser un número no negativo, con hasta 2 decimales (recibido: '$value').");
        }
        return $value;
    }
}
