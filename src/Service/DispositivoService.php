<?php
declare(strict_types=1);

namespace SpecHub\Api\Service;

use PDO;
use SpecHub\Api\DAO\DispositivoDAO;
use SpecHub\Api\DAO\MarcaDAO;
use SpecHub\Api\DAO\TipoDispositivoDAO;
use SpecHub\Api\Exception\NotFoundException;
use SpecHub\Api\Exception\ValidationException;
use SpecHub\Api\Mapper\DispositivoMapper;
use Throwable;

/**
 * Reglas de negocio de los dispositivos: verificación de llaves foráneas,
 * transacciones (dispositivo + ficha técnica se guardan juntos o no se guardan)
 * y armado de la respuesta enriquecida.
 */
final class DispositivoService
{
    public function __construct(
        private PDO $pdo,
        private DispositivoDAO $dao,
        private MarcaDAO $marcaDao,
        private TipoDispositivoDAO $tipoDao,
        private DispositivoMapper $mapper
    ) {
    }

    /** @return list<array<string,mixed>> */
    public function listar(?string $tipo, ?int $marcaId, ?string $precioMaximo, ?string $texto): array
    {
        return $this->armar($this->dao->buscar($tipo, $marcaId, $precioMaximo, $texto));
    }

    /** @return array<string,mixed> */
    public function obtener(int $id): array
    {
        $fila = $this->dao->buscarPorId($id) ?? throw new NotFoundException("Dispositivo no encontrado: $id");
        return $this->armar([$fila])[0];
    }

    /**
     * @param  array<string,mixed> $datos ya validados
     * @return array<string,mixed>
     */
    public function crear(array $datos): array
    {
        $this->verificarReferencias($datos);

        $id = $this->transaccion(function () use ($datos): int {
            $id = $this->dao->insertar($datos);
            $this->dao->reemplazarEspecificaciones($id, $datos['specs']);
            return $id;
        });

        return $this->obtener($id);
    }

    /**
     * PUT = reemplazo completo del recurso (incluida la ficha técnica).
     *
     * @param  array<string,mixed> $datos ya validados
     * @return array<string,mixed>
     */
    public function actualizar(int $id, array $datos): array
    {
        if (!$this->dao->existe($id)) {
            throw new NotFoundException("Dispositivo no encontrado: $id");
        }
        $this->verificarReferencias($datos);

        $this->transaccion(function () use ($id, $datos): void {
            $this->dao->actualizar($id, $datos);
            $this->dao->reemplazarEspecificaciones($id, $datos['specs']);
        });

        return $this->obtener($id);
    }

    public function eliminar(int $id): void
    {
        if (!$this->dao->eliminar($id)) { // cascada a specs, imágenes y comentarios
            throw new NotFoundException("Dispositivo no encontrado: $id");
        }
    }

    /** @param array<string,mixed> $datos */
    private function verificarReferencias(array $datos): void
    {
        $errores = [];
        if (!$this->marcaDao->existe($datos['brandId'])) {
            $errores['brandId'] = "Marca no encontrada: {$datos['brandId']}";
        }
        if (!$this->tipoDao->existe($datos['typeId'])) {
            $errores['typeId'] = "Tipo de dispositivo no encontrado: {$datos['typeId']}";
        }
        if ($errores !== []) {
            throw new ValidationException($errores);
        }
    }

    /**
     * Une cada dispositivo con sus tablas hijas usando 3 consultas en total,
     * sin importar cuántos dispositivos haya (evita N+1).
     *
     * @param  list<array<string,mixed>> $filas
     * @return list<array<string,mixed>>
     */
    private function armar(array $filas): array
    {
        if ($filas === []) {
            return [];
        }
        $ids = array_map(fn(array $f): int => (int) $f['id'], $filas);
        $specs = $this->dao->especificacionesDe($ids);
        $imagenes = $this->dao->imagenesDe($ids);
        $comentarios = $this->dao->comentariosDe($ids);

        return array_map(
            fn(array $f): array => $this->mapper->aDto(
                $f,
                $specs[(int) $f['id']] ?? [],
                $imagenes[(int) $f['id']] ?? [],
                $comentarios[(int) $f['id']] ?? []
            ),
            $filas
        );
    }

    private function transaccion(callable $operacion): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $resultado = $operacion();
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
