<?php
declare(strict_types=1);

namespace SpecHub\Api;

use PDO;
use RuntimeException;
use SpecHub\Api\Core\Container;
use SpecHub\Api\Core\Database;
use SpecHub\Api\Core\Env;
use SpecHub\Api\Core\ErrorResponder;
use SpecHub\Api\Core\Request;
use SpecHub\Api\Core\Response;
use SpecHub\Api\Core\Router;
use SpecHub\Api\Exception\NotFoundException;
use SpecHub\Api\Middleware\CorsMiddleware;
use SpecHub\Api\Service\JwtService;
use Throwable;

/**
 * Raíz de composición: arma el contenedor, registra las rutas y convierte
 * Request -> Response, garantizando que SIEMPRE se responda JSON (incluso ante errores).
 */
final class Kernel
{
    private Container $container;
    private Router $router;

    public function __construct()
    {
        $this->container = new Container();
        $this->container->set(PDO::class, static fn(): PDO => Database::connect());
        $this->container->set(JwtService::class, static function (): JwtService {
            $secret = Env::get('JWT_SECRET');
            if ($secret === null || strlen($secret) < 32) {
                throw new RuntimeException('JWT_SECRET no está configurado o tiene menos de 32 caracteres (revisa el archivo .env).');
            }
            return new JwtService($secret, (int) Env::get('JWT_EXPIRATION_SECONDS', '86400'));
        });

        $this->router = new Router($this->container);
        (require dirname(__DIR__) . '/routes/api.php')($this->router);
    }

    public function handle(Request $request): Response
    {
        try {
            if ($request->method === 'OPTIONS') {
                // Preflight CORS: se responde sin ejecutar controladores ni autenticación.
                $metodos = $this->router->allowedMethods($request->path);
                if ($metodos === []) {
                    throw new NotFoundException("Ruta no encontrada: {$request->path}");
                }
                $response = Response::noContent(['Allow' => implode(', ', [...$metodos, 'OPTIONS'])]);
            } else {
                $response = $this->router->dispatch($request);
            }
        } catch (Throwable $e) {
            $response = ErrorResponder::from($e, Env::bool('APP_DEBUG'));
        }

        return (new CorsMiddleware())->aplicar($request, $response);
    }
}
