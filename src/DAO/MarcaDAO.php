<?php
declare(strict_types=1);

namespace SpecHub\Api\DAO;

use PDO;

/** Acceso a datos de la tabla `marca`. */
final class MarcaDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array{id:int,nombre:string,pais:?string}> */
    public function listar(): array
    {
        return $this->pdo->query('SELECT id, nombre, pais FROM marca ORDER BY id')->fetchAll();
    }

    /** @return array{id:int,nombre:string,pais:?string}|null */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, pais FROM marca WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function existe(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM marca WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetchColumn() !== false;
    }

    /** Comparación sin distinguir mayúsculas (lo define la collation de la tabla). */
    public function existePorNombre(string $nombre, ?int $excluirId = null): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM marca WHERE nombre = ? AND id <> ?');
        $stmt->execute([$nombre, $excluirId ?? 0]);
        return $stmt->fetchColumn() !== false;
    }

    public function insertar(string $nombre, ?string $pais): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO marca (nombre, pais) VALUES (?, ?)');
        $stmt->execute([$nombre, $pais]);
        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(int $id, string $nombre, ?string $pais): void
    {
        $stmt = $this->pdo->prepare('UPDATE marca SET nombre = ?, pais = ? WHERE id = ?');
        $stmt->execute([$nombre, $pais, $id]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM marca WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
