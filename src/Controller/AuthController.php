<?php
declare(strict_types=1);

namespace SpecHub\Api\Controller;

use SpecHub\Api\Core\Request;
use SpecHub\Api\Core\Response;
use SpecHub\Api\Service\AuthService;
use SpecHub\Api\Validation\LoginValidator;

/** POST /api/auth/login · devuelve el JWT que exigen las operaciones de escritura. */
final class AuthController
{
    public function __construct(
        private AuthService $service,
        private LoginValidator $validator
    ) {
    }

    public function login(Request $request): Response
    {
        $datos = $this->validator->validar($request->jsonBody());
        return Response::json($this->service->login($datos['username'], $datos['password']));
    }
}
