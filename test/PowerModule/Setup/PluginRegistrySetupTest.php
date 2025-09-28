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
use Modular\Framework\Container\ConfigurableContainer;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\Setup\PowerModuleSetupDto;
use Modular\Framework\PowerModule\Setup\SetupPhase;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\Exception\PluginRegistryNotFoundException;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer3\CsvExtractPlugin;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer3\CsvExtractPluginRelyOnPluginRegistryInterfaceModule;
use PHPUnit\Framework\TestCase;

class PluginRegistrySetupTest extends TestCase
{
    public function testItShouldSkipPrePhase(): void
    {
        $rootContainer = $this->createMock(ConfigurableContainerInterface::class);
        $rootContainer->expects($this->never())
            ->method('has')
        ;
        $powerModuleSetupDto = new PowerModuleSetupDto(
            setupPhase: SetupPhase::Pre,
            powerModule: new CsvExtractPluginRelyOnPluginRegistryInterfaceModule(),
            rootContainer: $rootContainer,
            moduleContainer: new ConfigurableContainer(),
            modularAppConfig: Config::create(),
        );

        new PluginRegistrySetup()->setup($powerModuleSetupDto);
    }

    public function testItShouldSkipModuleThatDoesNotImplementProvidesPlugins(): void
    {
        $rootContainer = $this->createMock(ConfigurableContainerInterface::class);
        $rootContainer->expects($this->never())
            ->method('has')
        ;
        $powerModuleSetupDto = new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: new class () implements PowerModule {
                public function register(ConfigurableContainerInterface $container): void
                {
                }
            },
            rootContainer: $rootContainer,
            moduleContainer: new ConfigurableContainer(),
            modularAppConfig: Config::create(),
        );

        new PluginRegistrySetup()->setup($powerModuleSetupDto);
    }

    public function testItShouldRegisterPlugins(): void
    {
        $moduleContainer = $this->createMock(ConfigurableContainerInterface::class);
        $rootContainer = $this->createMock(ConfigurableContainerInterface::class);
        $pluginRegistry = $this->createMock(PluginRegistry::class);

        $rootContainer->expects($this->once())
            ->method('has')
            ->with(PluginRegistry::class)
            ->willReturn(true)
        ;

        $rootContainer->expects($this->once())
            ->method('get')
            ->with(PluginRegistry::class)
            ->willReturn($pluginRegistry)
        ;

        $pluginRegistry->expects($this->once())
            ->method('registerPlugin')
            ->with(CsvExtractPlugin::class, $moduleContainer)
        ;

        $powerModuleSetupDto = new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: new CsvExtractPluginRelyOnPluginRegistryInterfaceModule(),
            rootContainer: $rootContainer,
            moduleContainer: $moduleContainer,
            modularAppConfig: Config::create(),
        );

        new PluginRegistrySetup()->setup($powerModuleSetupDto);
    }

    public function testItShouldThrowExceptionIfNoPluginRegistryFound(): void
    {
        $rootContainer = $this->createMock(ConfigurableContainerInterface::class);
        $moduleContainer = $this->createMock(ConfigurableContainerInterface::class);
        $rootContainer->expects($this->once())
            ->method('has')
            ->with(PluginRegistry::class)
            ->willReturn(false)
        ;
        $rootContainer->expects($this->never())
            ->method('get')
        ;
        $powerModuleSetupDto = new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: new CsvExtractPluginRelyOnPluginRegistryInterfaceModule(),
            rootContainer: $rootContainer,
            moduleContainer: $moduleContainer,
            modularAppConfig: Config::create(),
        );

        $this->expectException(PluginRegistryNotFoundException::class);
        new PluginRegistrySetup()->setup($powerModuleSetupDto);
    }
}
