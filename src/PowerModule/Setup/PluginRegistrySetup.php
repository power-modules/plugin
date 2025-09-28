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

namespace Modular\Plugin\PowerModule\Setup;

use Modular\Framework\PowerModule\Contract\PowerModuleSetup;
use Modular\Framework\PowerModule\Setup\PowerModuleSetupDto;
use Modular\Framework\PowerModule\Setup\SetupPhase;
use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\Exception\PluginRegistryNotFoundException;
use Modular\Plugin\GenericPluginRegistry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Setup class that automatically registers plugins from PowerModules implementing ProvidesPlugins.
 *
 * This setup runs in the Post phase and processes modules that implement ProvidesPlugins interface.
 * For each plugin registry class specified in getPlugins(), it resolves the registry instance using
 * the following lookup policy:
 * - Root container only (single-source-of-truth)
 * - Throws PluginRegistryNotFoundException if not found
 *
 * Common usage patterns:
 * - Use PluginRegistry::class to target the default GenericPluginRegistry (automatically available when using withDefaults())
 * - Use GenericPluginRegistry::class to explicitly target the generic implementation
 * - Use custom PluginRegistry implementation class names for specialized registries
 * - Custom registries must be available in the root container before this setup runs (either exported by a module or bound programmatically)
 *
 * @see GenericPluginRegistrySetup for automatic GenericPluginRegistry provisioning
 */
readonly class PluginRegistrySetup implements PowerModuleSetup
{
    /**
     * @return array<PowerModuleSetup>
     */
    public static function withDefaults(): array
    {
        return [
            new GenericPluginRegistrySetup(),
            new PluginRegistryModuleConvenienceSetup(),
            new PluginRegistrySetup(),
        ];
    }

    /**
     * This setup runs in the Post phase to ensure all modules are registered.
     * It checks if the module implements ProvidesPlugins and registers its plugins
     * in the appropriate PluginRegistry.
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function setup(PowerModuleSetupDto $powerModuleSetupDto): void
    {
        if ($powerModuleSetupDto->setupPhase !== SetupPhase::Post) {
            return;
        }

        if ($powerModuleSetupDto->powerModule instanceof ProvidesPlugins === false) {
            return;
        }

        foreach ($powerModuleSetupDto->powerModule->getPlugins() as $registryClass => $pluginClasses) {
            $pluginRegistry = $this->getPluginRegistry($registryClass, $powerModuleSetupDto);

            foreach ($pluginClasses as $pluginClass) {
                $pluginRegistry->registerPlugin($pluginClass, $powerModuleSetupDto->moduleContainer);
            }
        }
    }

    /**
     * @return PluginRegistry<Plugin>
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function getPluginRegistry(
        string $registryClass,
        PowerModuleSetupDto $powerModuleSetupDto,
    ): PluginRegistry {
        if ($powerModuleSetupDto->rootContainer->has($registryClass)) {
            return $powerModuleSetupDto->rootContainer->get($registryClass);
        }

        throw new PluginRegistryNotFoundException($registryClass);
    }
}
