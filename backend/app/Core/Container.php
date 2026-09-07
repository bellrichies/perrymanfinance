<?php

declare(strict_types=1);

namespace PerrymanFinance\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;

final class Container
{
    /** @var array<string, Closure(self): mixed> */
    private array $bindings = [];
    /** @var array<string, mixed> */
    private array $instances = [];
    /** @var array<string, true> */
    private array $singletons = [];

    public function bind(string $id, Closure|string|null $concrete = null): void
    {
        $this->bindings[$id] = $this->factory($concrete ?? $id);
    }

    public function singleton(string $id, Closure|string|null $concrete = null): void
    {
        $this->bind($id, $concrete);
        $this->singletons[$id] = true;
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        $value = isset($this->bindings[$id]) ? ($this->bindings[$id])($this) : $this->build($id);
        if (isset($this->singletons[$id])) {
            $this->instances[$id] = $value;
        }
        return $value;
    }

    private function factory(Closure|string $concrete): Closure
    {
        return $concrete instanceof Closure
            ? $concrete
            : static fn (self $container): mixed => $container->build($concrete);
    }

    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new ContainerException("Cannot resolve {$class}.");
        }
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new ContainerException("{$class} is not instantiable.");
        }
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }
        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                throw new ContainerException("Cannot resolve parameter {$parameter->getName()} for {$class}.");
            }
        }
        return $reflection->newInstanceArgs($arguments);
    }
}
