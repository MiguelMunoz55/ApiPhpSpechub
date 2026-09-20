<?php
declare(strict_types=1);

namespace SpecHub\Api\Validation;

use DateTimeImmutable;
use SpecHub\Api\Exception\ValidationException;

/**
 * Utilidad de validación de entrada. Acumula TODOS los errores (campo => mensaje)
 * y los lanza juntos, para que el cliente corrija todo en un solo intento.
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errores = [];

    /** @param array<string,mixed> $input */
    public function __construct(private array $input)
    {
    }

    public function valor(string $campo): mixed
    {
        return $this->input[$campo] ?? null;
    }

    public function error(string $campo, string $mensaje): void
    {
        $this->errores[$campo] ??= $mensaje;
    }

    /** Texto obligatorio, recortado, con largo máximo. */
    public function texto(string $campo, string $msgObligatorio, string $etiqueta, int $max): ?string
    {
        $v = $this->valor($campo);
        if ($v === null || (is_string($v) && trim($v) === '')) {
            $this->error($campo, $msgObligatorio);
            return null;
        }
        if (!is_string($v)) {
            $this->error($campo, "$etiqueta debe ser un texto");
            return null;
        }
        $v = trim($v);
        if (mb_strlen($v) > $max) {
            $this->error($campo, "$etiqueta no puede superar $max caracteres");
            return null;
        }
        return $v;
    }

    /** Texto opcional: null o vacío => null. */
    public function textoOpcional(string $campo, string $etiqueta, int $max): ?string
    {
        $v = $this->valor($campo);
        if ($v === null || (is_string($v) && trim($v) === '')) {
            return null;
        }
        if (!is_string($v)) {
            $this->error($campo, "$etiqueta debe ser un texto");
            return null;
        }
        $v = trim($v);
        if (mb_strlen($v) > $max) {
            $this->error($campo, "$etiqueta no puede superar $max caracteres");
            return null;
        }
        return $v;
    }

    /** Identificador (llave foránea): entero positivo. */
    public function idPositivo(string $campo, string $msgObligatorio, string $etiqueta): ?int
    {
        $v = $this->valor($campo);
        if ($v === null || $v === '') {
            $this->error($campo, $msgObligatorio);
            return null;
        }
        if (is_string($v) && ctype_digit($v) && strlen($v) <= 18) {
            $v = (int) $v;
        }
        if (!is_int($v) || $v < 1) {
            $this->error($campo, "$etiqueta debe ser un entero positivo");
            return null;
        }
        return $v;
    }

    /** Fecha calendario real con formato AAAA-MM-DD. */
    public function fecha(string $campo, string $msgObligatorio, string $etiqueta): ?string
    {
        $v = $this->valor($campo);
        if ($v === null || $v === '') {
            $this->error($campo, $msgObligatorio);
            return null;
        }
        if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
            $e = DateTimeImmutable::getLastErrors();
            if ($d !== false && $d->format('Y-m-d') === $v && (!$e || ($e['warning_count'] === 0 && $e['error_count'] === 0))) {
                return $v;
            }
        }
        $this->error($campo, "$etiqueta debe ser una fecha válida con formato AAAA-MM-DD");
        return null;
    }

    /** Monto >= 0 con hasta 2 decimales (DECIMAL(12,2)). Se devuelve como string "1234.50". */
    public function monto(string $campo, string $msgObligatorio, string $msgNegativo, string $etiqueta): ?string
    {
        $v = $this->valor($campo);
        if ($v === null || $v === '') {
            $this->error($campo, $msgObligatorio);
            return null;
        }
        if (is_bool($v) || !is_numeric($v)) {
            $this->error($campo, "$etiqueta debe ser un número");
            return null;
        }
        $n = (float) $v;
        if ($n < 0) {
            $this->error($campo, $msgNegativo);
            return null;
        }
        if ($n > 9999999999.99) {
            $this->error($campo, "$etiqueta es demasiado grande (máximo 9999999999.99)");
            return null;
        }
        return number_format($n, 2, '.', '');
    }

    public function lanzarSiHayErrores(): void
    {
        if ($this->errores !== []) {
            throw new ValidationException($this->errores);
        }
    }
}
