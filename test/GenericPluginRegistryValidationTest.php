<?php

declare(strict_types=1);

namespace Modular\Plugin\Test;

use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\Exception\InvalidPluginImplementationException;
use Modular\Plugin\GenericPluginRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class GenericPluginRegistryValidationTest extends TestCase
{
    public function testRegisterPluginThrowsWhenClassMissing(): void
    {
        $registry = new GenericPluginRegistry();
        $container = $this->createMock(ContainerInterface::class);

        $this->expectException(InvalidPluginImplementationException::class);
        $this->expectExceptionMessage('Class does not exist');
        // @phpstan-ignore-next-line
        $registry->registerPlugin('NonExistent\\Class', $container);
    }

    public function testRegisterPluginThrowsWhenNotAPlugin(): void
    {
        $registry = new GenericPluginRegistry();
        $container = $this->createMock(ContainerInterface::class);

        // Use this test class as a non-plugin
        $this->expectException(InvalidPluginImplementationException::class);
        $this->expectExceptionMessage('Does not implement Plugin interface');
        // @phpstan-ignore-next-line
        $registry->registerPlugin(self::class, $container);
    }

    public function testResolveAllReturnsInstances(): void
    {
        $registry = new GenericPluginRegistry();

        // Define a tiny plugin inline via anonymous class
        $pluginClass = new class () implements Plugin {
            public static function getPluginMetadata(): \Modular\Plugin\PluginMetadata
            {
                return new \Modular\Plugin\PluginMetadata('X', '1.0.0');
            }
        };
        $className = $pluginClass::class;

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with($className)->willReturn($pluginClass);

        $registry->registerPlugin($className, $container);
        $all = $registry->resolveAll();

        self::assertCount(1, $all);
        self::assertSame($pluginClass, $all[0]);
    }
}
