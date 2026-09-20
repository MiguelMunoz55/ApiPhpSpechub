<?php
declare(strict_types=1);

namespace SpecHub\Api\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Contenedor de inyección de dependencias con autowiring por constructor.
 * Resuelve cada clase una sola vez (singleton) a partir de los tipos
 * declarados en su constructor. Para dependencias que necesitan configuración
 * (PDO, JwtService) se registra una fábrica con set().
 */
final class Container
{
    /** @var array<string,object> */
    private array $instances = [];
    /** @var array<string,callable> */
    private array $factories = [];

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (isset($this->factories[$id])) {
            return $this->instances[$id] = ($this->factories[$id])($this);
        }

        $reflection = new ReflectionClass($id);
        $constructor = $reflection->getConstructor();
        $args = [];
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $args[] = $this->get($type->getName());
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new RuntimeException("No se puede resolver el parámetro \${$param->getName()} de $id");
                }
            }
        }
        return $this->instances[$id] = $reflection->newInstanceArgs($args);
    }
}
