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

namespace Modular\Plugin\Exception;

use Exception;

class PluginRegistryNotFoundException extends Exception
{
    public function __construct(string $registryClass)
    {
        parent::__construct(
            "Plugin registry {$registryClass} not found in root container. " .
            'Registries are resolved from ROOT only. Export a custom registry from a module (so it becomes available during Pre) '
            . 'or bind it programmatically to the root container before Post. '
            . 'The default registry is provided by GenericPluginRegistrySetup under ' . \Modular\Plugin\Contract\PluginRegistry::class . '.',
        );
    }
}
