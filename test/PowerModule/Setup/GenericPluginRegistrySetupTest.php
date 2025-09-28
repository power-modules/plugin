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
use PHPUnit\Framework\TestCase;

class GenericPluginRegistrySetupTest extends TestCase
{
    public function testItShouldWorkInPostPhaseAndBindToRoot(): void
    {
        $genericPluginRegistry = new GenericPluginRegistry();
        $genericPluginRegistrySetup = new GenericPluginRegistrySetup($genericPluginRegistry);

        self::assertCount(0, $genericPluginRegistry->getRegisteredPlugins());
        $moduleContainer = new ConfigurableContainer();
        $rootContainer = new ConfigurableContainer();
        $module = new class () implements PowerModule {
            public function register(ConfigurableContainerInterface $container): void
            {
            }
        };
        $setupDto = new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: $module,
            moduleContainer: $moduleContainer,
            rootContainer: $rootContainer,
            modularAppConfig: Config::create(),
        );
        $genericPluginRegistrySetup->setup($setupDto);

        self::assertCount(0, $genericPluginRegistry->getRegisteredPlugins());

        // Registry should be bound in ROOT container under PluginRegistry::class
        self::assertTrue($rootContainer->has(PluginRegistry::class));
        self::assertFalse($rootContainer->has(GenericPluginRegistry::class));
        self::assertSame($genericPluginRegistry, $rootContainer->get(PluginRegistry::class));

        // Module container should not receive bindings from this setup
        self::assertFalse($moduleContainer->has(PluginRegistry::class));
        self::assertFalse($moduleContainer->has(GenericPluginRegistry::class));
    }

    public function testItShouldSkipModuleIfRootContainerHasPluginRegistry(): void
    {
        $defaultPluginRegistry = new GenericPluginRegistry();
        $genericPluginRegistrySetup = new GenericPluginRegistrySetup($defaultPluginRegistry);

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());
        $moduleContainer = new ConfigurableContainer();
        $rootContainer = new ConfigurableContainer();
        $rootContainer->set(PluginRegistry::class, new GenericPluginRegistry());
        $module = new class () implements PowerModule {
            public function register(ConfigurableContainerInterface $container): void
            {
            }
        };
        $setupDto = new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: $module,
            moduleContainer: $moduleContainer,
            rootContainer: $rootContainer,
            modularAppConfig: Config::create(),
        );
        $genericPluginRegistrySetup->setup($setupDto);

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());

        self::assertTrue($rootContainer->has(PluginRegistry::class));
        self::assertFalse($rootContainer->has(GenericPluginRegistry::class));

        self::assertFalse($moduleContainer->has(PluginRegistry::class));
        self::assertFalse($moduleContainer->has(GenericPluginRegistry::class));
    }

    public function testItShouldSkipModuleIfRootContainerHasGenericPluginPluginRegistry(): void
    {
        $defaultPluginRegistry = new GenericPluginRegistry();
        $genericPluginRegistrySetup = new GenericPluginRegistrySetup($defaultPluginRegistry);

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());
        $moduleContainer = new ConfigurableContainer();
        $rootContainer = new ConfigurableContainer();
        $rootContainer->set(GenericPluginRegistry::class, new GenericPluginRegistry());
        $module = new class () implements PowerModule {
            public function register(ConfigurableContainerInterface $container): void
            {
            }
        };
        $setupDto = new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: $module,
            moduleContainer: $moduleContainer,
            rootContainer: $rootContainer,
            modularAppConfig: Config::create(),
        );
        $genericPluginRegistrySetup->setup($setupDto);

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());

        self::assertFalse($rootContainer->has(PluginRegistry::class));
        self::assertTrue($rootContainer->has(GenericPluginRegistry::class));

        self::assertFalse($moduleContainer->has(PluginRegistry::class));
        self::assertFalse($moduleContainer->has(GenericPluginRegistry::class));
    }

    public function testItShouldBindToRootEvenIfModuleContainerHasPluginRegistry(): void
    {
        $defaultPluginRegistry = new GenericPluginRegistry();
        $genericPluginRegistrySetup = new GenericPluginRegistrySetup($defaultPluginRegistry);

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());
        $moduleContainer = new ConfigurableContainer();
        $moduleContainer->set(PluginRegistry::class, new GenericPluginRegistry());
        $rootContainer = new ConfigurableContainer();
        $module = new class () implements PowerModule {
            public function register(ConfigurableContainerInterface $container): void
            {
            }
        };
        $setupDto = new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: $module,
            moduleContainer: $moduleContainer,
            rootContainer: $rootContainer,
            modularAppConfig: Config::create(),
        );
        $genericPluginRegistrySetup->setup($setupDto);

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());

        // Root should now have the default registry bound regardless of module binding
        self::assertTrue($rootContainer->has(PluginRegistry::class));
        self::assertFalse($rootContainer->has(GenericPluginRegistry::class));

        // Module container retains its own binding
        self::assertTrue($moduleContainer->has(PluginRegistry::class));
        self::assertFalse($moduleContainer->has(GenericPluginRegistry::class));
    }

    public function testItShouldBindToRootEvenIfModuleContainerHasGenericPluginRegistry(): void
    {
        $defaultPluginRegistry = new GenericPluginRegistry();
        $genericPluginRegistrySetup = new GenericPluginRegistrySetup($defaultPluginRegistry);

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());
        $moduleContainer = new ConfigurableContainer();
        $moduleContainer->set(GenericPluginRegistry::class, new GenericPluginRegistry());
        $rootContainer = new ConfigurableContainer();
        $module = new class () implements PowerModule {
            public function register(ConfigurableContainerInterface $container): void
            {
            }
        };
        $setupDto = new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: $module,
            moduleContainer: $moduleContainer,
            rootContainer: $rootContainer,
            modularAppConfig: Config::create(),
        );
        $genericPluginRegistrySetup->setup($setupDto);

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());

        // Root should now have the default registry bound regardless of module binding
        self::assertTrue($rootContainer->has(PluginRegistry::class));
        self::assertFalse($rootContainer->has(GenericPluginRegistry::class));

        // Module container retains its own binding
        self::assertFalse($moduleContainer->has(PluginRegistry::class));
        self::assertTrue($moduleContainer->has(GenericPluginRegistry::class));
    }

    public function testItShouldRegisterGenericPluginRegistryInRoot(): void
    {
        $defaultPluginRegistry = new GenericPluginRegistry();
        $genericPluginRegistrySetup = new GenericPluginRegistrySetup($defaultPluginRegistry);

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());
        $moduleContainer = new ConfigurableContainer();
        $rootContainer = new ConfigurableContainer();
        $module = new class () implements PowerModule {
            public function register(ConfigurableContainerInterface $container): void
            {
            }
        };
        $setupDto = new PowerModuleSetupDto(
            setupPhase: SetupPhase::Post,
            powerModule: $module,
            moduleContainer: $moduleContainer,
            rootContainer: $rootContainer,
            modularAppConfig: Config::create(),
        );
        $genericPluginRegistrySetup->setup($setupDto);

        self::assertCount(0, $defaultPluginRegistry->getRegisteredPlugins());

        // Registry should be bound in ROOT container under PluginRegistry::class
        self::assertTrue($rootContainer->has(PluginRegistry::class));
        self::assertFalse($rootContainer->has(GenericPluginRegistry::class));
        self::assertSame($defaultPluginRegistry, $rootContainer->get(PluginRegistry::class));

        // Module container should remain untouched by this setup
        self::assertFalse($moduleContainer->has(PluginRegistry::class));
        self::assertFalse($moduleContainer->has(GenericPluginRegistry::class));
    }
}
