<?php
declare(strict_types=1);

use SpecHub\Api\Controller\AuthController;
use SpecHub\Api\Controller\DispositivoController;
use SpecHub\Api\Controller\InfoController;
use SpecHub\Api\Controller\MarcaController;
use SpecHub\Api\Controller\TipoDispositivoController;
use SpecHub\Api\Core\Router;
use SpecHub\Api\Middleware\AuthMiddleware;

/**
 * Tabla de rutas de la API. Es el "contrato" REST del proyecto:
 * Lectura = pública. Escritura (POST/PUT/DELETE) = requiere JWT de ADMIN.
 */
return static function (Router $r): void {
    $admin = [AuthMiddleware::class];

    $r->get('/api', [InfoController::class, 'index']);

    // --- Autenticación ---
    $r->post('/api/auth/login', [AuthController::class, 'login']);

    // --- Dispositivos (recurso principal) ---
    $r->get('/api/devices',         [DispositivoController::class, 'listar']);
    $r->get('/api/devices/{id}',    [DispositivoController::class, 'obtener']);
    $r->post('/api/devices',        [DispositivoController::class, 'crear'], $admin);
    $r->put('/api/devices/{id}',    [DispositivoController::class, 'actualizar'], $admin);
    $r->delete('/api/devices/{id}', [DispositivoController::class, 'eliminar'], $admin);

    // --- Marcas ---
    $r->get('/api/brands',         [MarcaController::class, 'listar']);
    $r->get('/api/brands/{id}',    [MarcaController::class, 'obtener']);
    $r->post('/api/brands',        [MarcaController::class, 'crear'], $admin);
    $r->put('/api/brands/{id}',    [MarcaController::class, 'actualizar'], $admin);
    $r->delete('/api/brands/{id}', [MarcaController::class, 'eliminar'], $admin);

    // --- Tipos de dispositivo (solo lectura) ---
    $r->get('/api/device-types', [TipoDispositivoController::class, 'listar']);
};
