<?php

declare(strict_types=1);

namespace RzxLib\Core\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;

/**
 * Simple Service Container
 *
 * @package RzxLib\Core\Container
 */
class Container
{
    /**
     * The container's bindings.
     */
    protected array $bindings = [];

    /**
     * The container's shared instances.
     */
    protected array $instances = [];

    /**
     * The registered type aliases.
     */
    protected array $aliases = [];

    /**
     * Register a binding with the container.
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'shared' => $shared,
        ];
    }

    /**
     * Register a shared binding in the container.
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container.
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Alias a type to a different name.
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Resolve the given type from the container.
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        // Check for alias
        $abstract = $this->getAlias($abstract);

        // If we have an instance, return it
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get the concrete type
        $concrete = $this->getConcrete($abstract);

        // Build the instance
        $object = $this->build($concrete, $parameters);

        // If it's a shared binding, store the instance
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get the alias for an abstract if available.
     */
    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Get the concrete type for a given abstract.
     */
    protected function getConcrete(string $abstract): Closure|string
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Determine if a given type is shared.
     */
    protected function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Instantiate a concrete instance of the given type.
     */
    protected function build(Closure|string $concrete, array $parameters = []): mixed
    {
        // If the concrete type is a Closure, execute it and return the result
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        // If the concrete is a string (class name), use reflection
        $reflector = new ReflectionClass($concrete);

        // Make sure the class is instantiable
        if (!$reflector->isInstantiable()) {
            throw new \Exception("Target [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // If there is no constructor, just instantiate and return
        if ($constructor === null) {
            return new $concrete();
        }

        // Resolve constructor dependencies
        $dependencies = $this->resolveDependencies(
            $constructor->getParameters(),
            $parameters
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     */
    protected function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            // If we have a primitive override, use it
            if (array_key_exists($name, $primitives)) {
                $dependencies[] = $primitives[$name];
                continue;
            }

            // Resolve the dependency
            $dependencies[] = $this->resolveParameter($parameter);
        }

        return $dependencies;
    }

    /**
     * Resolve a single parameter.
     */
    protected function resolveParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        // If it's a class, try to resolve it
        if ($type !== null && !$type->isBuiltin()) {
            $className = $type->getName();

            try {
                return $this->make($className);
            } catch (\Exception $e) {
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }
                throw $e;
            }
        }

        // If it has a default value, use that
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // If it allows null, return null
        if ($parameter->allowsNull()) {
            return null;
        }

        throw new \Exception("Unable to resolve dependency [{$parameter->getName()}]");
    }

    /**
     * Determine if the given abstract type has been bound.
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               isset($this->aliases[$abstract]);
    }

    /**
     * Flush the container of all bindings and instances.
     */
    public function flush(): void
    {
        $this->aliases = [];
        $this->bindings = [];
        $this->instances = [];
    }
}
