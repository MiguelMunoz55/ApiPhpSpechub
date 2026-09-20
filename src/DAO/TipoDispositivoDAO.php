<?php
declare(strict_types=1);

namespace SpecHub\Api\DAO;

use PDO;

/** Acceso a datos de la tabla `tipo_dispositivo` (solo lectura). */
final class TipoDispositivoDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array{id:int,nombre:string,slug:string}> */
    public function listar(): array
    {
        return $this->pdo->query('SELECT id, nombre, slug FROM tipo_dispositivo ORDER BY id')->fetchAll();
    }

    public function existe(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM tipo_dispositivo WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetchColumn() !== false;
    }
}
