<?php
declare(strict_types=1);

namespace SpecHub\Api\Core;

use PDO;

/**
 * Fábrica de la conexión PDO. Se usa como "factory" en el contenedor de
 * dependencias, de modo que la conexión solo se abre cuando algún DAO la necesita.
 */
final class Database
{
    public static function connect(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Env::get('DB_HOST', '127.0.0.1'),
            Env::get('DB_PORT', '3306'),
            Env::get('DB_NAME', 'spechub')
        );

        return new PDO($dsn, Env::get('DB_USER', 'root'), Env::get('DB_PASS', ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Sentencias preparadas reales (no emuladas): protege contra inyección SQL
            // y devuelve los enteros como int.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
}
