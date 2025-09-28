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

namespace Modular\Plugin\Test\PowerModule\Setup\Stub\Layer3;

use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer2\Extractor;

/**
 * @implements ProvidesPlugins<Extractor&Plugin>
 */
class CsvExtractPluginRelyOnPluginRegistryInterfaceModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [ // register in the external/default PluginRegistry
                CsvExtractPlugin::class,
            ],
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(
            CsvExtractPlugin::class,
            CsvExtractPlugin::class,
        );
    }
}
