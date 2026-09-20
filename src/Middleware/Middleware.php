<?php
declare(strict_types=1);

namespace SpecHub\Api\Middleware;

use SpecHub\Api\Core\Request;
use SpecHub\Api\Core\Response;

/** Contrato de un middleware: puede cortar la petición (lanzando una excepción) o delegar en $next. */
interface Middleware
{
    /** @param callable(Request): Response $next */
    public function handle(Request $request, callable $next): Response;
}
