<?php

declare(strict_types=1);

namespace App\Core;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Simple Dependency Injection Container
 * 
 * Implements PSR-11 ContainerInterface.
 * Supports automatic dependency resolution.
 */
class Container implements ContainerInterface
{
    /**
     * @var array<string, mixed> Registered instances
     */
    private array $instances = [];

    /**
     * @var array<string, callable|string> Registered factories
     */
    private array $factories = [];

    /**
     * @var array<string, bool> Resolving lock
     */
    private array $resolving = [];

    /**
     * Get a service from the container
     *
     * @param string $id Service ID
     * @return mixed
     * @throws \Exception
     */
    public function get(string $id): mixed
    {
        // Check if instance already exists (singleton)
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Check if factory exists
        if (isset($this->factories[$id])) {
            return $this->resolveFactory($id);
        }

        // Try to auto-resolve class
        if (class_exists($id)) {
            return $this->resolveClass($id);
        }

        throw new \Exception("Service not found: {$id}");
    }

    /**
     * Check if a service exists in the container
     *
     * @param string $id Service ID
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || 
               isset($this->factories[$id]) || 
               class_exists($id);
    }

    /**
     * Register a service instance
     *
     * @param string $id Service ID
     * @param mixed $instance Instance
     * @return self
     */
    public function set(string $id, mixed $instance): self
    {
        $this->instances[$id] = $instance;
        return $this;
    }

    /**
     * Register a singleton service
     *
     * @param string $id Service ID
     * @param callable|string $factory Factory or class name
     * @return self
     */
    public function singleton(string $id, callable|string $factory): self
    {
        $this->factories[$id] = ['singleton' => true, 'factory' => $factory];
        return $this;
    }

    /**
     * Register a transient service (new instance each time)
     *
     * @param string $id Service ID
     * @param callable|string $factory Factory or class name
     * @return self
     */
    public function transient(string $id, callable|string $factory): self
    {
        $this->factories[$id] = ['singleton' => false, 'factory' => $factory];
        return $this;
    }

    /**
     * Remove a service from the container
     *
     * @param string $id Service ID
     * @return self
     */
    public function remove(string $id): self
    {
        unset($this->instances[$id], $this->factories[$id]);
        return $this;
    }

    /**
     * Clear all instances (for testing)
     *
     * @return self
     */
    public function clear(): self
    {
        $this->instances = [];
        $this->factories = [];
        $this->resolving = [];
        return $this;
    }

    /**
     * Resolve a factory
     *
     * @param string $id Service ID
     * @return mixed
     * @throws \Exception
     */
    private function resolveFactory(string $id): mixed
    {
        $factory = $this->factories[$id];
        $isSingleton = $factory['singleton'];

        // Check circular dependency
        if ($this->isResolving($id)) {
            throw new \Exception("Circular dependency detected for: {$id}");
        }

        // For singletons, return cached instance
        if ($isSingleton && isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $this->setResolving($id);

        try {
            $result = is_callable($factory['factory']) 
                ? $factory['factory']($this)
                : $this->resolveClass($factory['factory']);

            if ($isSingleton) {
                $this->instances[$id] = $result;
            }

            $this->unsetResolving($id);

            return $result;
        } catch (\Throwable $e) {
            $this->unsetResolving($id);
            throw $e;
        }
    }

    /**
     * Resolve a class using reflection
     *
     * @param string $class Class name
     * @return object
     * @throws ReflectionException
     */
    private function resolveClass(string $class): object
    {
        // Check circular dependency
        if ($this->isResolving($class)) {
            throw new \Exception("Circular dependency detected for: {$class}");
        }

        $reflector = new ReflectionClass($class);

        // Check if class is instantiable
        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$class} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = $constructor->getParameters();
        $resolvedDeps = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($resolvedDeps);
    }

    /**
     * Resolve constructor dependencies
     *
     * @param array<ReflectionParameter> $dependencies
     * @return array
     * @throws \Exception
     */
    private function resolveDependencies(array $dependencies): array
    {
        $resolved = [];

        foreach ($dependencies as $dependency) {
            $type = $dependency->getType();

            if ($type === null) {
                // No type hint, check for default value
                if ($dependency->isDefaultValueAvailable()) {
                    $resolved[] = $dependency->getDefaultValue();
                } else {
                    throw new \Exception(
                        "Cannot resolve parameter: {$dependency->getName()}"
                    );
                }
                continue;
            }

            $typeName = $type->getName();

            // Skip built-in types
            if (!$type->isBuiltin()) {
                try {
                    $resolved[] = $this->get($typeName);
                } catch (\Exception $e) {
                    if ($dependency->isDefaultValueAvailable()) {
                        $resolved[] = $dependency->getDefaultValue();
                    } elseif ($type->allowsNull()) {
                        $resolved[] = null;
                    } else {
                        throw $e;
                    }
                }
            } else {
                // Built-in type, use default value
                if ($dependency->isDefaultValueAvailable()) {
                    $resolved[] = $dependency->getDefaultValue();
                } else {
                    throw new \Exception(
                        "Cannot resolve builtin parameter: {$dependency->getName()}"
                    );
                }
            }
        }

        return $resolved;
    }

    /**
     * Check if service is being resolved
     *
     * @param string $id Service ID
     * @return bool
     */
    private function isResolving(string $id): bool
    {
        return isset($this->resolving[$id]);
    }

    /**
     * Mark service as resolving
     *
     * @param string $id Service ID
     * @return void
     */
    private function setResolving(string $id): void
    {
        $this->resolving[$id] = true;
    }

    /**
     * Mark service as resolved
     *
     * @param string $id Service ID
     * @return void
     */
    private function unsetResolving(string $id): void
    {
        unset($this->resolving[$id]);
    }
}
