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

class PluginNotRegisteredException extends Exception
{
    public function __construct(string $registryClass, string $pluginClass)
    {
        parent::__construct(
            "Plugin {$pluginClass} is not registered in {$registryClass}. " .
            'Ensure your module implements ProvidesPlugins and that PluginRegistrySetup ran. '
            . 'If you assemble pipelines from config, verify class names and that the module providing the plugin is loaded.',
        );
    }
}
