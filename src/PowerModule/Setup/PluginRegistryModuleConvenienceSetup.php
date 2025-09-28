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
use Modular\Plugin\Contract\PluginRegistry;

/**
 * Convenience setup: makes PluginRegistry available in each module container for DI.
 *
 * Policy: Root remains the single source of truth. This setup runs in Post and copies
 * the PluginRegistry service definition from root into the module container if missing.
 * This avoids requiring every user module to implement ImportsComponents just to inject
 * the registry into its services.
 *
 * Safe behavior: If PluginRegistry is not present in root (e.g., custom order), this setup
 * does nothing for that module. Using PluginRegistrySetup::withDefaults() ensures the default
 * registry is provided beforehand via GenericPluginRegistrySetup.
 */
final readonly class PluginRegistryModuleConvenienceSetup implements PowerModuleSetup
{
    public function setup(PowerModuleSetupDto $powerModuleSetupDto): void
    {
        if ($powerModuleSetupDto->setupPhase !== SetupPhase::Post) {
            return;
        }

        // Root must have PluginRegistry; if not, skip silently.
        if ($powerModuleSetupDto->rootContainer->has(PluginRegistry::class) === false) {
            return;
        }

        // If module already has a binding (via explicit ImportsComponents or manual), respect it.
        if ($powerModuleSetupDto->moduleContainer->has(PluginRegistry::class)) {
            return;
        }

        // Copy the root definition into the module container so DI can resolve it locally.
        $powerModuleSetupDto->moduleContainer->addServiceDefinition(
            PluginRegistry::class,
            $powerModuleSetupDto->rootContainer->getServiceDefinition(PluginRegistry::class),
        );
    }
}
