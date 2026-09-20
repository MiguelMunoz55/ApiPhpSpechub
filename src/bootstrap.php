<?php
declare(strict_types=1);

use SpecHub\Api\Core\Env;

/**
 * Arranque común: autoload PSR-4, variables de entorno y manejo de errores de PHP.
 * No requiere Composer: basta con PHP 8.1+ (extensiones pdo_mysql y mbstring).
 */

// Autoload PSR-4: SpecHub\Api\Foo\Bar  ->  src/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'SpecHub\\Api\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

Env::load(dirname(__DIR__) . '/.env');

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// Nunca imprimir errores de PHP en la respuesta: rompería el JSON y filtraría rutas internas.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Convierte warnings/notices en excepciones para que terminen en una respuesta JSON 500 controlada.
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
