<?php
declare(strict_types=1);

/**
 * Front controller: TODAS las peticiones entran por aquí.
 * (Apache: ver .htaccess · Servidor embebido: php -S localhost:8000 -t public public/index.php)
 */
require __DIR__ . '/../src/bootstrap.php';

use SpecHub\Api\Core\Request;
use SpecHub\Api\Kernel;

(new Kernel())->handle(Request::fromGlobals())->send();
