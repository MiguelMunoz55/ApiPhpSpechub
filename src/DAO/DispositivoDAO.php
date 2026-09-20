<?php
declare(strict_types=1);

namespace SpecHub\Api\DAO;

use PDO;

/**
 * Acceso a datos de la tabla `dispositivo` y sus tablas hijas
 * (especificacion_dispositivo, imagen_dispositivo, comentario).
 * Todas las consultas usan sentencias preparadas.
 */
final class DispositivoDAO
{
    private const SELECT_BASE = <<<'SQL'
        SELECT d.id, d.nombre, d.marca_id, d.tipo_id, d.fecha_lanzamiento, d.precio,
               d.descripcion_corta, d.resena, d.tono_imagen,
               m.nombre AS marca_nombre, m.pais AS marca_pais,
               t.nombre AS tipo_nombre, t.slug AS tipo_slug
          FROM dispositivo d
          JOIN marca m ON m.id = d.marca_id
          JOIN tipo_dispositivo t ON t.id = d.tipo_id
        SQL;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Filtro combinado equivalente a GET /api/devices?type=&brandId=&maxPrice=&q=
     * Cada filtro es opcional.
     *
     * @return list<array<string,mixed>>
     */
    public function buscar(?string $slugTipo, ?int $marcaId, ?string $precioMaximo, ?string $texto): array
    {
        $where = [];
        $params = [];
        if ($slugTipo !== null) {
            $where[] = 't.slug = ?';
            $params[] = $slugTipo;
        }
        if ($marcaId !== null) {
            $where[] = 'd.marca_id = ?';
            $params[] = $marcaId;
        }
        if ($precioMaximo !== null) {
            $where[] = 'd.precio <= ?';
            $params[] = $precioMaximo;
        }
        if ($texto !== null) {
            // Se escapan % y _ para que el usuario no pueda usarlos como comodines.
            $where[] = "d.nombre LIKE ? ESCAPE '\\\\'";
            $params[] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto) . '%';
        }

        $sql = self::SELECT_BASE . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY d.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(self::SELECT_BASE . ' WHERE d.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function existe(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM dispositivo WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetchColumn() !== false;
    }

    /** @param array<string,mixed> $d datos validados */
    public function insertar(array $d): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO dispositivo (nombre, marca_id, tipo_id, fecha_lanzamiento, precio, descripcion_corta, resena, tono_imagen)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['name'], $d['brandId'], $d['typeId'], $d['releaseDate'], $d['price'],
            $d['shortDescription'], $d['review'], $d['imageTone'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string,mixed> $d datos validados */
    public function actualizar(int $id, array $d): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE dispositivo
                SET nombre = ?, marca_id = ?, tipo_id = ?, fecha_lanzamiento = ?, precio = ?,
                    descripcion_corta = ?, resena = ?, tono_imagen = ?
              WHERE id = ?'
        );
        $stmt->execute([
            $d['name'], $d['brandId'], $d['typeId'], $d['releaseDate'], $d['price'],
            $d['shortDescription'], $d['review'], $d['imageTone'], $id,
        ]);
    }

    /** La ficha técnica se reemplaza completa (semántica de PUT). @param array<string,string> $specs */
    public function reemplazarEspecificaciones(int $dispositivoId, array $specs): void
    {
        $del = $this->pdo->prepare('DELETE FROM especificacion_dispositivo WHERE dispositivo_id = ?');
        $del->execute([$dispositivoId]);

        if ($specs === []) {
            return;
        }
        $filas = [];
        $params = [];
        $orden = 0;
        foreach ($specs as $clave => $valor) {
            $filas[] = '(?, ?, ?, ?)';
            array_push($params, $dispositivoId, (string) $clave, $valor, $orden++);
        }
        $ins = $this->pdo->prepare(
            'INSERT INTO especificacion_dispositivo (dispositivo_id, clave, valor, orden) VALUES ' . implode(', ', $filas)
        );
        $ins->execute($params);
    }

    /** Borra el dispositivo; specs, imágenes y comentarios caen por ON DELETE CASCADE. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM dispositivo WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function contarPorMarca(int $marcaId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM dispositivo WHERE marca_id = ?');
        $stmt->execute([$marcaId]);
        return (int) $stmt->fetchColumn();
    }

    // ---- Carga por lotes de las tablas hijas (evita el problema N+1) ----

    /** @param list<int> $ids @return array<int,list<array<string,mixed>>> */
    public function especificacionesDe(array $ids): array
    {
        return $this->hijos(
            'SELECT dispositivo_id, clave, valor FROM especificacion_dispositivo WHERE dispositivo_id IN (%s) ORDER BY dispositivo_id, orden, id',
            $ids
        );
    }

    /** @param list<int> $ids @return array<int,list<array<string,mixed>>> */
    public function imagenesDe(array $ids): array
    {
        return $this->hijos(
            'SELECT dispositivo_id, url_imagen FROM imagen_dispositivo WHERE dispositivo_id IN (%s) ORDER BY dispositivo_id, orden, id',
            $ids
        );
    }

    /** @param list<int> $ids @return array<int,list<array<string,mixed>>> */
    public function comentariosDe(array $ids): array
    {
        return $this->hijos(
            'SELECT id, dispositivo_id, nombre_autor, calificacion, contenido, creado_en FROM comentario WHERE dispositivo_id IN (%s) ORDER BY dispositivo_id, creado_en DESC, id DESC',
            $ids
        );
    }

    /**
     * @param list<int> $ids
     * @return array<int,list<array<string,mixed>>> filas agrupadas por dispositivo_id
     */
    private function hijos(string $sqlConPlaceholder, array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(sprintf($sqlConPlaceholder, $marcas));
        $stmt->execute($ids);

        $agrupado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $agrupado[(int) $fila['dispositivo_id']][] = $fila;
        }
        return $agrupado;
    }
}
