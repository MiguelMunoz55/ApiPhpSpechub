<?php
declare(strict_types=1);

namespace SpecHub\Api\Core;

use SpecHub\Api\Exception\MethodNotAllowedException;
use SpecHub\Api\Exception\NotFoundException;
use SpecHub\Api\Middleware\Middleware;

/**
 * Router mínimo: asocia (método, patrón) con un método de controlador y una
 * lista opcional de middlewares. Los patrones admiten parámetros: /api/devices/{id}
 */
final class Router
{
    /** @var list<array{method:string, regex:string, handler:array{0:class-string,1:string}, middleware:list<class-string<Middleware>>}> */
    private array $routes = [];

    public function __construct(private Container $container)
    {
    }

    /**
     * @param array{0:class-string,1:string}      $handler    [Controlador::class, 'metodo']
     * @param list<class-string<Middleware>>      $middleware
     */
    public function add(string $method, string $pattern, array $handler, array $middleware = []): void
    {
        $regex = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = ['method' => $method, 'regex' => $regex, 'handler' => $handler, 'middleware' => $middleware];
    }

    public function get(string $p, array $h, array $m = []): void    { $this->add('GET', $p, $h, $m); }
    public function post(string $p, array $h, array $m = []): void   { $this->add('POST', $p, $h, $m); }
    public function put(string $p, array $h, array $m = []): void    { $this->add('PUT', $p, $h, $m); }
    public function delete(string $p, array $h, array $m = []): void { $this->add('DELETE', $p, $h, $m); }

    public function dispatch(Request $request): Response
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }
            if ($route['method'] !== $request->method) {
                $allowed[] = $route['method'];
                continue;
            }

            $request->params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            // Pipeline: middlewares (de afuera hacia adentro) y al final el controlador.
            $core = function (Request $req) use ($route): Response {
                [$class, $method] = $route['handler'];
                return $this->container->get($class)->$method($req);
            };
            $pipeline = array_reduce(
                array_reverse($route['middleware']),
                fn(callable $next, string $mw): callable =>
                    fn(Request $req): Response => $this->container->get($mw)->handle($req, $next),
                $core
            );
            return $pipeline($request);
        }

        if ($allowed !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowed)));
        }
        throw new NotFoundException("Ruta no encontrada: {$request->method} {$request->path}");
    }

    /** Métodos admitidos para una ruta (usado por las respuestas OPTIONS). */
    public function allowedMethods(string $path): array
    {
        $methods = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path)) {
                $methods[] = $route['method'];
            }
        }
        return array_values(array_unique($methods));
    }
}
