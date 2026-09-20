<?php
declare(strict_types=1);

namespace SpecHub\Api\Controller;

use SpecHub\Api\Core\Request;
use SpecHub\Api\Core\Response;
use SpecHub\Api\Service\DispositivoService;
use SpecHub\Api\Validation\DispositivoValidator;

/**
 * Recurso /api/devices — CRUD completo del recurso principal.
 *
 *   GET    /api/devices        200  lista (filtros opcionales)   público
 *   GET    /api/devices/{id}   200  detalle                      público
 *   POST   /api/devices        201  crea (+ cabecera Location)   ADMIN
 *   PUT    /api/devices/{id}   200  reemplaza                    ADMIN
 *   DELETE /api/devices/{id}   204  elimina                      ADMIN
 */
final class DispositivoController
{
    public function __construct(
        private DispositivoService $service,
        private DispositivoValidator $validator
    ) {
    }

    // GET /api/devices?type=celular&brandId=1&maxPrice=5000000&q=galaxy
    public function listar(Request $request): Response
    {
        return Response::json($this->service->listar(
            $request->queryString('type'),
            $request->queryInt('brandId'),
            $request->queryDecimal('maxPrice'),
            $request->queryString('q')
        ));
    }

    public function obtener(Request $request): Response
    {
        return Response::json($this->service->obtener($request->intParam('id')));
    }

    public function crear(Request $request): Response
    {
        $datos = $this->validator->validar($request->jsonBody());
        $creado = $this->service->crear($datos);

        return Response::json($creado, 201, [
            'Location' => $request->basePath . '/api/devices/' . $creado['id'],
        ]);
    }

    public function actualizar(Request $request): Response
    {
        $id = $request->intParam('id');
        $datos = $this->validator->validar($request->jsonBody());
        return Response::json($this->service->actualizar($id, $datos));
    }

    public function eliminar(Request $request): Response
    {
        $this->service->eliminar($request->intParam('id'));
        return Response::noContent();
    }
}
