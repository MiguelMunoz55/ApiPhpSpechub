<?php
declare(strict_types=1);

namespace SpecHub\Api\Service;

use PDOException;
use SpecHub\Api\DAO\DispositivoDAO;
use SpecHub\Api\DAO\MarcaDAO;
use SpecHub\Api\Exception\ConflictException;
use SpecHub\Api\Exception\NotFoundException;

/** Reglas de negocio de las marcas: nombre único y no borrar marcas en uso. */
final class MarcaService
{
    public function __construct(
        private MarcaDAO $dao,
        private DispositivoDAO $dispositivoDao
    ) {
    }

    /** @return list<array{id:int,name:string,country:?string}> */
    public function listar(): array
    {
        return array_map($this->aDto(...), $this->dao->listar());
    }

    /** @return array{id:int,name:string,country:?string} */
    public function obtener(int $id): array
    {
        return $this->aDto($this->dao->buscarPorId($id) ?? throw new NotFoundException("Marca no encontrada: $id"));
    }

    /**
     * @param  array{name:string,country:?string} $datos
     * @return array{id:int,name:string,country:?string}
     */
    public function crear(array $datos): array
    {
        if ($this->dao->existePorNombre($datos['name'])) {
            throw new ConflictException('Ya existe una marca con ese nombre');
        }
        try {
            $id = $this->dao->insertar($datos['name'], $datos['country']);
        } catch (PDOException $e) {
            throw $this->traducir($e);
        }
        return $this->obtener($id);
    }

    /**
     * @param  array{name:string,country:?string} $datos
     * @return array{id:int,name:string,country:?string}
     */
    public function actualizar(int $id, array $datos): array
    {
        if (!$this->dao->existe($id)) {
            throw new NotFoundException("Marca no encontrada: $id");
        }
        if ($this->dao->existePorNombre($datos['name'], $id)) {
            throw new ConflictException('Ya existe otra marca con ese nombre');
        }
        try {
            $this->dao->actualizar($id, $datos['name'], $datos['country']);
        } catch (PDOException $e) {
            throw $this->traducir($e);
        }
        return $this->obtener($id);
    }

    public function eliminar(int $id): void
    {
        if (!$this->dao->existe($id)) {
            throw new NotFoundException("Marca no encontrada: $id");
        }
        if ($this->dispositivoDao->contarPorMarca($id) > 0) {
            throw new ConflictException('No se puede eliminar la marca: tiene dispositivos asociados');
        }
        try {
            $this->dao->eliminar($id);
        } catch (PDOException $e) {
            throw $this->traducir($e);
        }
    }

    /** Red de seguridad ante condiciones de carrera: la BD también protege la integridad. */
    private function traducir(PDOException $e): \Throwable
    {
        return match ((int) ($e->errorInfo[1] ?? 0)) {
            1062 => new ConflictException('Ya existe una marca con ese nombre'),
            1451 => new ConflictException('No se puede eliminar la marca: tiene dispositivos asociados'),
            default => $e,
        };
    }

    /** @param array{id:int,nombre:string,pais:?string} $fila */
    private function aDto(array $fila): array
    {
        return ['id' => (int) $fila['id'], 'name' => $fila['nombre'], 'country' => $fila['pais']];
    }
}
