<?php
declare(strict_types=1);

namespace SpecHub\Api\Controller;

use SpecHub\Api\Core\Request;
use SpecHub\Api\Core\Response;
use SpecHub\Api\Service\MarcaService;
use SpecHub\Api\Validation\MarcaValidator;

/** Recurso /api/brands — CRUD completo de marcas. */
final class MarcaController
{
    public function __construct(
        private MarcaService $service,
        private MarcaValidator $validator
    ) {
    }

    public function listar(Request $request): Response
    {
        return Response::json($this->service->listar());
    }

    public function obtener(Request $request): Response
    {
        return Response::json($this->service->obtener($request->intParam('id')));
    }

    public function crear(Request $request): Response
    {
        $creada = $this->service->crear($this->validator->validar($request->jsonBody()));
        return Response::json($creada, 201, ['Location' => $request->basePath . '/api/brands/' . $creada['id']]);
    }

    public function actualizar(Request $request): Response
    {
        $id = $request->intParam('id');
        return Response::json($this->service->actualizar($id, $this->validator->validar($request->jsonBody())));
    }

    public function eliminar(Request $request): Response
    {
        $this->service->eliminar($request->intParam('id'));
        return Response::noContent();
    }
}
