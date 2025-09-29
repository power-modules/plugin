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

class PluginAlreadyRegisteredException extends Exception
{
    public function __construct(string $registryClass, string $pluginClass)
    {
        parent::__construct(
            "Plugin {$pluginClass} is already registered in {$registryClass}. " .
            'Duplicate registrations are not allowed; ensure each plugin class is declared once per registry.',
        );
    }
}
