<?php
declare(strict_types=1);

namespace SpecHub\Api\Service;

use SpecHub\Api\Exception\UnauthorizedException;

/**
 * Emisión y validación de tokens JWT (HS256) sin librerías externas.
 * El formato (claims "sub", "role", "iat", "exp" y el secreto usado como bytes
 * crudos) es el mismo que produce JwtService.java, por lo que con el mismo
 * secreto los tokens son intercambiables entre el backend Java y esta API.
 */
final class JwtService
{
    public function __construct(
        private string $secret,
        private int $ttlSeconds
    ) {
    }

    public function generar(string $username, string $role): string
    {
        $now = time();
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = ['role' => $role, 'sub' => $username, 'iat' => $now, 'exp' => $now + $this->ttlSeconds];

        $signingInput = $this->b64(json_encode($header, JSON_THROW_ON_ERROR)) . '.'
            . $this->b64(json_encode($payload, JSON_THROW_ON_ERROR));

        return $signingInput . '.' . $this->b64($this->firmar($signingInput));
    }

    /**
     * @return array<string,mixed> claims del token
     * @throws UnauthorizedException si el token es inválido o expiró
     */
    public function validar(string $token): array
    {
        $partes = explode('.', $token);
        if (count($partes) !== 3) {
            throw new UnauthorizedException('Token inválido');
        }
        [$h, $p, $s] = $partes;

        $header = json_decode((string) $this->b64d($h), true);
        // Se exige HS256 de forma explícita: evita ataques con alg=none o cambio de algoritmo.
        if (!is_array($header) || ($header['alg'] ?? null) !== 'HS256') {
            throw new UnauthorizedException('Token inválido');
        }

        $esperada = $this->firmar("$h.$p");
        $recibida = $this->b64d($s);
        if ($recibida === false || !hash_equals($esperada, $recibida)) {
            throw new UnauthorizedException('Token inválido');
        }

        $claims = json_decode((string) $this->b64d($p), true);
        if (!is_array($claims) || !isset($claims['exp'], $claims['sub'])) {
            throw new UnauthorizedException('Token inválido');
        }
        if ((int) $claims['exp'] < time()) {
            throw new UnauthorizedException('El token ha expirado, inicia sesión de nuevo');
        }
        return $claims;
    }

    private function firmar(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret, true);
    }

    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function b64d(string $encoded): string|false
    {
        return base64_decode(strtr($encoded, '-_', '+/'), true);
    }
}
