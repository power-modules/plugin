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
use Modular\Plugin\GenericPluginRegistry;
use Modular\Plugin\PowerModule\Setup\GenericPluginRegistrySetup;
use Modular\Plugin\PowerModule\Setup\PluginRegistryModuleConvenienceSetup;
use PHPUnit\Framework\TestCase;

final class PluginRegistryModuleConvenienceSetupTest extends TestCase
{
    public function testItCopiesRootRegistryDefinitionIntoModuleContainer(): void
    {
        $defaultRegistry = new GenericPluginRegistry();

        $moduleContainer = new ConfigurableContainer();
        $rootContainer = new ConfigurableContainer();

        $module = new class () implements PowerModule {
            public function register(ConfigurableContainerInterface $container): void
            {
            }
        };

        // First ensure root has PluginRegistry via GenericPluginRegistrySetup
        (new GenericPluginRegistrySetup($defaultRegistry))->setup(new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: $module,
            moduleContainer: $moduleContainer,
            rootContainer: $rootContainer,
            modularAppConfig: Config::create(),
        ));

        self::assertTrue($rootContainer->has(PluginRegistry::class));
        self::assertFalse($moduleContainer->has(PluginRegistry::class));

        // Now run the convenience setup
        (new PluginRegistryModuleConvenienceSetup())->setup(new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: $module,
            moduleContainer: $moduleContainer,
            rootContainer: $rootContainer,
            modularAppConfig: Config::create(),
        ));

        // Module container should now have access to PluginRegistry definition (same instance when resolved)
        self::assertTrue($moduleContainer->has(PluginRegistry::class));
        self::assertSame(
            $rootContainer->get(PluginRegistry::class),
            $moduleContainer->get(PluginRegistry::class),
        );
    }

    public function testItRespectsExistingModuleBinding(): void
    {
        $defaultRegistry = new GenericPluginRegistry();
        $customModuleRegistry = new GenericPluginRegistry();

        $moduleContainer = new ConfigurableContainer();
        $rootContainer = new ConfigurableContainer();
        $module = new class () implements PowerModule {
            public function register(ConfigurableContainerInterface $container): void
            {
            }
        };

        // Root gets the default registry
        (new GenericPluginRegistrySetup($defaultRegistry))->setup(new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: $module,
            moduleContainer: $moduleContainer,
            rootContainer: $rootContainer,
            modularAppConfig: Config::create(),
        ));

        // Module binds its own registry (simulate explicit override/import)
        $moduleContainer->set(PluginRegistry::class, $customModuleRegistry);

        // Run convenience setup; it should not override the module binding
        (new PluginRegistryModuleConvenienceSetup())->setup(new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: $module,
            moduleContainer: $moduleContainer,
            rootContainer: $rootContainer,
            modularAppConfig: Config::create(),
        ));

        self::assertTrue($moduleContainer->has(PluginRegistry::class));
        self::assertSame($customModuleRegistry, $moduleContainer->get(PluginRegistry::class));
        self::assertNotSame($rootContainer->get(PluginRegistry::class), $moduleContainer->get(PluginRegistry::class));
    }
}
