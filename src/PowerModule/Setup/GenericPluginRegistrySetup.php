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
use Modular\Plugin\GenericPluginRegistry;

/**
 * Ensures a single default GenericPluginRegistry is available in the ROOT container.
 *
 * Runs in the Post phase so that module exports (registered in Pre) are already
 * available. If a registry is already bound in the root (either via exports or
 * custom wiring), this setup does nothing. Otherwise, it binds a shared
 * GenericPluginRegistry instance under PluginRegistry::class.
 */
class GenericPluginRegistrySetup implements PowerModuleSetup
{
    /**
     * @param GenericPluginRegistry<Plugin> $defaultPluginRegistry
     */
    public function __construct(
        private readonly GenericPluginRegistry $defaultPluginRegistry = new GenericPluginRegistry(),
    ) {
    }

    public function setup(PowerModuleSetupDto $powerModuleSetupDto): void
    {
        // Post phase: exports are already registered to the root container.
        if ($powerModuleSetupDto->setupPhase !== SetupPhase::Post) {
            return;
        }

        // If a registry is already available in the ROOT container, leave it as-is.
        if ($powerModuleSetupDto->rootContainer->has(PluginRegistry::class)
            || $powerModuleSetupDto->rootContainer->has(GenericPluginRegistry::class)
        ) {
            return;
        }

        // Bind a single shared default registry in the ROOT container.
        $powerModuleSetupDto->rootContainer->set(PluginRegistry::class, $this->defaultPluginRegistry);
    }
}
