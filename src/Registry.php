<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator;

use yii\base\InvalidArgumentException;

/**
 * Singleton registry of all registered Configurator modules.
 *
 * Registered as a singleton by Bootstrap. Domain bootstraps call register() during
 * their bootstrap phase to add their Module instances.
 *
 * Usage in a domain Bootstrap:
 *   \Yii::$container->get(Registry::class)->register(\Yii::$container->get(PaymentModule::class));
 */
class Registry
{
    /** @var array<string, Module> */
    private array $modules = [];

    public function register(Module $module): void
    {
        $this->modules[$module->getKey()] = $module;
    }

    public function get(string $key): Module
    {
        return $this->modules[$key]
            ?? throw new InvalidArgumentException("Unknown configurator module: {$key}");
    }

    public function has(string $key): bool
    {
        return isset($this->modules[$key]);
    }

    /** @return Module[] */
    public function all(): array
    {
        return array_values($this->modules);
    }
}
