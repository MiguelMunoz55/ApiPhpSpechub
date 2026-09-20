<?php
declare(strict_types=1);

namespace SpecHub\Api\Core;

/**
 * Respuesta HTTP inmutable. Los controladores la construyen; solo el front
 * controller (public/index.php) la envía al cliente.
 */
final class Response
{
    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        private int $status = 200,
        private mixed $body = null,
        private array $headers = []
    ) {
    }

    /** @param array<string,string> $headers */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        return new self($status, $data, $headers);
    }

    /** @param array<string,string> $headers */
    public static function noContent(array $headers = []): self
    {
        return new self(204, null, $headers);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function withHeader(string $name, string $value): self
    {
        $copy = clone $this;
        $copy->headers[$name] = $value;
        return $copy;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        if ($this->status === 204 || $this->body === null) {
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            $this->body,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        );
    }
}
