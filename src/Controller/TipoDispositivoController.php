<?php
declare(strict_types=1);

namespace SpecHub\Api\Controller;

use SpecHub\Api\Core\Request;
use SpecHub\Api\Core\Response;
use SpecHub\Api\Service\TipoDispositivoService;

/** GET /api/device-types · catálogo de tipos (solo lectura). */
final class TipoDispositivoController
{
    public function __construct(private TipoDispositivoService $service)
    {
    }

    public function listar(Request $request): Response
    {
        return Response::json($this->service->listar());
    }
}
