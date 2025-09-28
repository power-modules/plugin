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

namespace Modular\Plugin\Test\PowerModule\Setup;

use Modular\Framework\App\Config\Config;
use Modular\Framework\App\Config\Setting;
use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\Exception\PluginRegistryNotFoundException;
use Modular\Plugin\GenericPluginRegistry;
use Modular\Plugin\PowerModule\Setup\GenericPluginRegistrySetup;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer2\ExtractModule;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer2\ExtractPluginRegistry;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer3\CsvExtractPlugin;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer3\CsvExtractPluginRelyOnCustomRegistryModule;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer3\CsvExtractPluginRelyOnPluginRegistryInterfaceModule;
use PHPUnit\Framework\TestCase;

class PluginRegistrySetupIntegrationTest extends TestCase
{
    public function testPluginIsRegisteredInDefaultRegistryIfRegistryInterfaceIsNotExportedByAnyOtherModule(): void
    {
        $defaultPluginRegistry = new GenericPluginRegistry();
        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());

        $app = new ModularAppBuilder(__DIR__)
            ->withConfig(Config::forAppRoot(__DIR__)->set(Setting::CachePath, sys_get_temp_dir()))
            ->withModules(
                CsvExtractPluginRelyOnPluginRegistryInterfaceModule::class,
            )
            ->withPowerSetup(
                new GenericPluginRegistrySetup($defaultPluginRegistry),
                new PluginRegistrySetup(),
            )
            ->build()
        ;

        // Default registry should be available in ROOT container now
        self::assertTrue($app->has(PluginRegistry::class));
        self::assertFalse($app->has(ExtractPluginRegistry::class));

        self::assertCount(1, $defaultPluginRegistry->getRegisteredPlugins());
        self::assertTrue($defaultPluginRegistry->hasPlugin(CsvExtractPlugin::class));

        // Verify plugin can be created and has correct metadata
        $plugin = $defaultPluginRegistry->makePlugin(CsvExtractPlugin::class);
        self::assertInstanceOf(CsvExtractPlugin::class, $plugin);

        $pluginMetadata = $plugin::getPluginMetadata();

        self::assertSame('CSV Extraction Plugin', $pluginMetadata->name);
        self::assertSame('1.0.0', $pluginMetadata->version);
        self::assertSame('A plugin to extract data from CSV files.', $pluginMetadata->description);
    }

    public function testPluginIsRegisteredInDesiredRegistryThatIsExportedBySomeModule(): void
    {
        $defaultPluginRegistry = new GenericPluginRegistry();
        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());

        $app = new ModularAppBuilder(__DIR__)
            ->withConfig(Config::forAppRoot(__DIR__)->set(Setting::CachePath, sys_get_temp_dir()))
            ->withModules(
                ExtractModule::class,
                CsvExtractPluginRelyOnCustomRegistryModule::class,
            )
            ->withPowerSetup(
                new GenericPluginRegistrySetup($defaultPluginRegistry),
                new PluginRegistrySetup(),
            )
            ->build()
        ;

        // Default registry still available; custom registry is exported by ExtractModule
        self::assertTrue($app->has(PluginRegistry::class));
        self::assertTrue($app->has(ExtractPluginRegistry::class));

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());
        self::assertFalse($defaultPluginRegistry->hasPlugin(CsvExtractPlugin::class));

        $extractPluginRegistry = $app->get(ExtractPluginRegistry::class);
        self::assertCount(1, $extractPluginRegistry->getRegisteredPlugins());
        self::assertTrue($extractPluginRegistry->hasPlugin(CsvExtractPlugin::class));

        // Verify plugin can be created and has correct metadata
        $plugin = $extractPluginRegistry->makePlugin(CsvExtractPlugin::class);
        self::assertInstanceOf(CsvExtractPlugin::class, $plugin);

        $pluginMetadata = $plugin::getPluginMetadata();

        self::assertSame('CSV Extraction Plugin', $pluginMetadata->name);
        self::assertSame('1.0.0', $pluginMetadata->version);
        self::assertSame('A plugin to extract data from CSV files.', $pluginMetadata->description);
    }

    public function testPluginRegistrationFailedIfNoCustomRegistryFound(): void
    {
        $defaultPluginRegistry = new GenericPluginRegistry();
        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());

        $this->expectException(PluginRegistryNotFoundException::class);

        $app = new ModularAppBuilder(__DIR__)
            ->withConfig(Config::forAppRoot(__DIR__)->set(Setting::CachePath, sys_get_temp_dir()))
            ->withModules(
                CsvExtractPluginRelyOnCustomRegistryModule::class,
            )
            ->withPowerSetup(
                new GenericPluginRegistrySetup($defaultPluginRegistry),
                new PluginRegistrySetup(),
            )
            ->build()
        ;
    }
}
