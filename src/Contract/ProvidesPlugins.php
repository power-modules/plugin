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

/**
 * Indicates that a PowerModule provides plugins to be registered in the PluginRegistry.
 *
 * @template TPlugin of Plugin
 * @see \Modular\Plugin\PowerModule\Setup\PluginRegistrySetup for more details.
 */
interface ProvidesPlugins
{
    /**
     * @return array<class-string<PluginRegistry<TPlugin>>,class-string<TPlugin>[]> List of plugin class names to be registered
     */
    public static function getPlugins(): array;
}
