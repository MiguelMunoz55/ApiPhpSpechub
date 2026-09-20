<?php
declare(strict_types=1);

namespace SpecHub\Api\Service;

use SpecHub\Api\DAO\UsuarioAdminDAO;
use SpecHub\Api\Exception\UnauthorizedException;

/** Autenticación de administradores contra la tabla usuario_admin (hash bcrypt). */
final class AuthService
{
    /** Hash de relleno: se verifica igual cuando el usuario no existe, para no revelar (por tiempo de respuesta) qué usuarios existen. */
    private const HASH_RELLENO = '$2y$10$Q1suHlHHDnxizz.j2PdrwOxnAEdnVG8DXs0pgdCLU4HOCFNxxMbeG';

    public function __construct(
        private UsuarioAdminDAO $dao,
        private JwtService $jwt
    ) {
    }

    /** @return array{token:string,username:string,role:string} */
    public function login(string $username, string $password): array
    {
        $usuario = $this->dao->buscarPorUsuario($username);
        $hash = $usuario['contrasena_hash'] ?? self::HASH_RELLENO;

        // Los hashes de Spring Security usan el prefijo $2b$ y PHP los verifica sin cambios.
        if (!password_verify($password, $hash) || $usuario === null) {
            throw new UnauthorizedException('Usuario o contraseña incorrectos');
        }

        return [
            'token'    => $this->jwt->generar($usuario['nombre_usuario'], $usuario['rol']),
            'username' => $usuario['nombre_usuario'],
            'role'     => $usuario['rol'],
        ];
    }
}
