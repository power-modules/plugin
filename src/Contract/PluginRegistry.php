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

namespace Modular\Plugin\Contract;

use Modular\Plugin\PluginMetadata;
use Psr\Container\ContainerInterface;

/**
 * @template TPlugin of Plugin
 */
interface PluginRegistry
{
    /**
     * @param class-string<TPlugin> $pluginClass
     * @param ContainerInterface $container The container to resolve plugin dependencies
     */
    public function registerPlugin(string $pluginClass, ContainerInterface $container): void;

    /**
     * @return class-string<TPlugin>[]
     */
    public function getRegisteredPlugins(): array;

    /**
     * @param class-string<TPlugin> $pluginClass
     */
    public function hasPlugin(string $pluginClass): bool;

    /**
     * @param class-string<TPlugin> $pluginClass
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @return TPlugin
     */
    public function makePlugin(string $pluginClass): Plugin;

    /**
     * Instantiate all registered plugins.
     *
     * @return list<TPlugin>
     */
    public function resolveAll(): array;

    /**
     * Return metadata for a registered plugin without instantiating it.
     *
     * @param class-string<TPlugin> $pluginClass
     * @throws \Modular\Plugin\Exception\PluginNotRegisteredException when plugin is not registered
     */
    public function getPluginMetadataFor(string $pluginClass): PluginMetadata;

    /**
     * List metadata for all registered plugins.
     *
     * @return array<class-string<TPlugin>,PluginMetadata>
     */
    public function listPluginMetadata(): array;
}
