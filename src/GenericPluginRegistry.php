<?php

/**
 * This file is part of the Plugin extension for the Modular Framework.
 *
 * (c) 2025 Evgenii Teterin
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Modular\Plugin;

use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\Exception\InvalidPluginImplementationException;
use Modular\Plugin\Exception\PluginAlreadyRegisteredException;
use Modular\Plugin\Exception\PluginNotRegisteredException;
use Psr\Container\ContainerInterface;

/**
 * @template TPlugin of Plugin - e.g. GenericPluginRegistry<MyPlugin>
 * @implements PluginRegistry<TPlugin>
 */
class GenericPluginRegistry implements PluginRegistry
{
    /**
     * @var array<class-string<TPlugin>,ContainerInterface> $registeredPlugins
     */
    protected array $registeredPlugins = [];

    public function registerPlugin(string $pluginClass, ContainerInterface $container): void
    {
        // Early validation: ensure class exists and implements Plugin
        if (class_exists($pluginClass) === false) {
            throw new InvalidPluginImplementationException(static::class, $pluginClass, 'Class does not exist');
        }

        // @phpstan-ignore-next-line
        if (is_subclass_of($pluginClass, Plugin::class) === false) {
            throw new InvalidPluginImplementationException(static::class, $pluginClass, 'Does not implement Plugin interface');
        }

        if (isset($this->registeredPlugins[$pluginClass])) {
            throw new PluginAlreadyRegisteredException(static::class, $pluginClass);
        }

        $this->registeredPlugins[$pluginClass] = $container;
    }

    public function getRegisteredPlugins(): array
    {
        return array_keys($this->registeredPlugins);
    }

    public function hasPlugin(string $pluginClass): bool
    {
        return array_key_exists($pluginClass, $this->registeredPlugins);
    }

    public function makePlugin(string $pluginClass): Plugin
    {
        if ($this->hasPlugin($pluginClass) === false) {
            throw new PluginNotRegisteredException(static::class, $pluginClass);
        }

        $container = $this->registeredPlugins[$pluginClass];
        $plugin = $container->get($pluginClass);

        if (!$plugin instanceof Plugin) {
            throw new \RuntimeException("Resolved instance for {$pluginClass} does not implement \"" . Plugin::class . "\". Check your container binding.");
        }

        // Type Modular\Plugin\Contract\Plugin is not always the same as TPlugin. It breaks the contract for subtypes.
        // But we cannot check it here, because TPlugin is not available at runtime.
        // I decided to ignore this error, to not overcomplicate the code because in practice it works as expected.
        // @phpstan-ignore-next-line
        return $plugin;
    }

    public function getPluginMetadataFor(string $pluginClass): PluginMetadata
    {
        if ($this->hasPlugin($pluginClass) === false) {
            throw new PluginNotRegisteredException(static::class, $pluginClass);
        }

        /** @var class-string<Plugin> $pluginClass */
        return $pluginClass::getPluginMetadata();
    }

    public function listPluginMetadata(): array
    {
        $result = [];
        foreach ($this->getRegisteredPlugins() as $class) {
            /** @var class-string<TPlugin> $class */
            $result[$class] = $class::getPluginMetadata();
        }

        return $result;
    }

    /**
     * @return list<TPlugin>
     */
    public function resolveAll(): array
    {
        $instances = [];
        foreach ($this->getRegisteredPlugins() as $class) {
            /** @var TPlugin $instance */
            $instance = $this->makePlugin($class);
            $instances[] = $instance;
        }

        return $instances;
    }
}
