<?php
declare(strict_types=1);

namespace SpecHub\Api\Controller;

use SpecHub\Api\Core\Request;
use SpecHub\Api\Core\Response;
use SpecHub\Api\Service\HealthService;

/** GET /api · información de la API y comprobación de conexión a la BD. */
final class InfoController
{
    public function __construct(private HealthService $health)
    {
    }

    public function index(Request $request): Response
    {
        $estado = $this->health->estado();
        return Response::json([
            'name'     => 'SpecHub API (PHP)',
            'version'  => '1.0.0',
            'status'   => $estado['status'],
            'database' => $estado['database'],
            'resources' => ['/api/devices', '/api/brands', '/api/device-types', '/api/auth/login'],
        ], $estado['status'] === 'ok' ? 200 : 503);
    }
}
