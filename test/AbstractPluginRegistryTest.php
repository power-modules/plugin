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

namespace Modular\Plugin\Test;

use Modular\Plugin\Exception\PluginNotRegisteredException;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer2\ExtractPluginRegistry;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer3\CsvExtractPlugin;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AbstractPluginRegistryTest extends TestCase
{
    private ExtractPluginRegistry $pluginRegistry;

    protected function setUp(): void
    {
        $this->pluginRegistry = new ExtractPluginRegistry();
    }

    public function testGetRegisteredPluginsReturnsEmptyArrayInitially(): void
    {
        self::assertCount(0, $this->pluginRegistry->getRegisteredPlugins());
        self::assertSame([], $this->pluginRegistry->getRegisteredPlugins());
    }

    public function testRegisterPluginStoresPlugin(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $this->pluginRegistry->registerPlugin(CsvExtractPlugin::class, $container);

        self::assertCount(1, $this->pluginRegistry->getRegisteredPlugins());
        self::assertSame([CsvExtractPlugin::class], $this->pluginRegistry->getRegisteredPlugins());
    }

    public function testHasPluginReturnsFalseForUnregisteredPlugin(): void
    {
        self::assertFalse($this->pluginRegistry->hasPlugin(CsvExtractPlugin::class));
    }

    public function testHasPluginReturnsTrueForRegisteredPlugin(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $this->pluginRegistry->registerPlugin(CsvExtractPlugin::class, $container);

        self::assertTrue($this->pluginRegistry->hasPlugin(CsvExtractPlugin::class));
    }

    public function testMakePluginThrowsExceptionForUnregisteredPlugin(): void
    {
        $this->expectException(PluginNotRegisteredException::class);

        $this->pluginRegistry->makePlugin(CsvExtractPlugin::class);
    }

    public function testMakePluginReturnsPluginInstanceForRegisteredPlugin(): void
    {
        $plugin = new CsvExtractPlugin();
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(CsvExtractPlugin::class)
            ->willReturn($plugin)
        ;

        $this->pluginRegistry->registerPlugin(CsvExtractPlugin::class, $container);

        $result = $this->pluginRegistry->makePlugin(CsvExtractPlugin::class);

        self::assertSame($plugin, $result);
    }

    public function testMakePluginThrowsExceptionWhenContainerReturnsNonPluginInstance(): void
    {
        $nonPlugin = new \stdClass();
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(CsvExtractPlugin::class)
            ->willReturn($nonPlugin)
        ;

        $this->pluginRegistry->registerPlugin(CsvExtractPlugin::class, $container);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resolved instance for ' . CsvExtractPlugin::class . ' does not implement "Modular\\Plugin\\Contract\\Plugin". Check your container binding.');

        $this->pluginRegistry->makePlugin(CsvExtractPlugin::class);
    }

    public function testRegisterMultiplePlugins(): void
    {
        $container1 = $this->createMock(ContainerInterface::class);
        $container2 = $this->createMock(ContainerInterface::class);

        $this->pluginRegistry->registerPlugin(CsvExtractPlugin::class, $container1);
        $this->pluginRegistry->registerPlugin(CsvExtractPlugin::class, $container2);

        self::assertCount(1, $this->pluginRegistry->getRegisteredPlugins());
        self::assertContains(CsvExtractPlugin::class, $this->pluginRegistry->getRegisteredPlugins());
        self::assertTrue($this->pluginRegistry->hasPlugin(CsvExtractPlugin::class));
    }
}
