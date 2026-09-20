<?php
declare(strict_types=1);

namespace SpecHub\Api\Validation;

/** Reglas del cuerpo de POST /api/auth/login (equivalente a LoginRequestDTO.java). */
final class LoginValidator
{
    /**
     * @param  array<string,mixed> $input
     * @return array{username:string, password:string}
     */
    public function validar(array $input): array
    {
        $v = new Validator($input);
        $username = $v->texto('username', 'El usuario es obligatorio', 'El usuario', 60);
        // La contraseña NO se recorta: los espacios pueden ser parte de ella.
        $password = $v->valor('password');
        if (!is_string($password) || $password === '') {
            $v->error('password', 'La contraseña es obligatoria');
        }
        $v->lanzarSiHayErrores();
        return ['username' => $username, 'password' => $password];
    }
}
