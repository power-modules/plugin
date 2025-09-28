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

namespace Modular\Plugin\Test\PowerModule\Setup\Stub\Layer2;

use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;

/**
 * Layer 2 module is responsible for exporting the \Modular\Plugin\Contract\PluginRegistry implementation for the domain.
 * This registry will be used by the Layer 3 module to register its plugins.
 */
class ExtractModule implements PowerModule, ExportsComponents
{
    public static function exports(): array
    {
        return [
            ExtractPluginRegistry::class,
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(
            ExtractPluginRegistry::class,
            ExtractPluginRegistry::class,
        );
    }
}
