<?php
declare(strict_types=1);

namespace SpecHub\Api\DAO;

use PDO;

/** Acceso a datos de la tabla `usuario_admin`. */
final class UsuarioAdminDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{id:int,nombre_usuario:string,contrasena_hash:string,rol:string}|null */
    public function buscarPorUsuario(string $nombreUsuario): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre_usuario, contrasena_hash, rol FROM usuario_admin WHERE nombre_usuario = ?');
        $stmt->execute([$nombreUsuario]);
        return $stmt->fetch() ?: null;
    }
}
