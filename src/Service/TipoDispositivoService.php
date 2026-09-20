<?php
declare(strict_types=1);

namespace SpecHub\Api\Service;

use SpecHub\Api\DAO\TipoDispositivoDAO;

/** Consulta de tipos de dispositivo (celular, portátil, tablet, smartwatch). */
final class TipoDispositivoService
{
    public function __construct(private TipoDispositivoDAO $dao)
    {
    }

    /** @return list<array{id:int,name:string,slug:string}> */
    public function listar(): array
    {
        return array_map(
            fn(array $t): array => ['id' => (int) $t['id'], 'name' => $t['nombre'], 'slug' => $t['slug']],
            $this->dao->listar()
        );
    }
}
