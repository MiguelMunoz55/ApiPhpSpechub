<?php
declare(strict_types=1);

namespace SpecHub\Api\Service;

use SpecHub\Api\Core\Database;
use Throwable;

/**
 * Diagnóstico rápido: ¿la API está viva y llega a la base de datos?
 * Abre su propia conexión (en vez de recibir PDO por constructor) para poder
 * reportar "degraded" en lugar de fallar cuando la BD está caída.
 */
final class HealthService
{
    /** @return array{status:string,database:string} */
    public function estado(): array
    {
        try {
            Database::connect()->query('SELECT 1');
            return ['status' => 'ok', 'database' => 'ok'];
        } catch (Throwable) {
            return ['status' => 'degraded', 'database' => 'unreachable'];
        }
    }
}
