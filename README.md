# Plugin System Extension

[![CI](https://github.com/power-modules/plugin/actions/workflows/php.yml/badge.svg)](https://github.com/power-modules/plugin/actions/workflows/php.yml)
[![Packagist Version](https://img.shields.io/packagist/v/power-modules/plugin)](https://packagist.org/packages/power-modules/plugin)
[![PHP Version](https://img.shields.io/packagist/php-v/power-modules/plugin)](https://packagist.org/packages/power-modules/plugin)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-blue)](#)

A plugin architecture library for the Power Modules framework that uses PHPDoc generics for IDE/PHPStan type safety, enabling extensible plugin systems with automatic discovery, dependency injection, and seamless integration.

> **🔌 Extensible:** Designed for applications that need third-party extensions, dynamic feature loading, or modular plugin architectures.

## ✨ Architectural Vision

The Plugin System Extension is not just an add-on; it's a foundational library that brings a new level of type safety and developer experience to building extensible PHP applications. It is built on the principle that plugins should be first-class citizens in the application architecture, with full access to dependency injection and module encapsulation.

Our goal is to provide a system that is both powerful and easy to use, enabling developers to create complex plugin-based systems with confidence.

## 🚀 Quick Start

```bash
composer require power-modules/plugin
```

Define a plugin, provide it from a module, and the framework handles the rest.

```php
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\PluginMetadata;

// 1. Define a plugin
final class MyPlugin implements Plugin
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('My Plugin', '1.0.0', 'Example plugin');
    }
}

// 2. Provide it from a module
final class MyModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [PluginRegistry::class => [MyPlugin::class]];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(MyPlugin::class, MyPlugin::class);
    }
}

// 3. The application automatically discovers and registers it
$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(MyModule::class)
    ->build();

// 4. Use it
/** @var PluginRegistry<MyPlugin> $registry */
$registry = $app->get(PluginRegistry::class);
$plugin = $registry->makePlugin(MyPlugin::class);
```

## 📚 Documentation

**📖 [Complete Documentation Hub](docs/README.md)** - Comprehensive guides, examples, and API reference.

**Quick Links:**
- **[Getting Started](docs/getting-started.md)** - Build your first plugin in 5 minutes.
- **[Architecture Guide](docs/architecture.md)** - Deep dive into the plugin system's internals.
- **[API Reference](docs/api-reference.md)** - Complete interface and class documentation.

## 🌟 Key Features

- **Enhanced Developer Experience**: Generic annotations provide better IDE support and static analysis.
- **Automatic Discovery**: `PluginRegistrySetup` scans modules implementing `ProvidesPlugins` and registers declared plugin classes.
- **Lazy Loading**: Plugins are instantiated only when requested through registries.
- **Container Integration**: Full dependency injection support for plugins.
- **Multiple Registries**: Support for specialized plugin types with custom registries.
- **Framework Native**: Built specifically for the Power Modules architecture.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup and guidelines.

## License

MIT License. See [LICENSE](LICENSE) for details.

---

*The Plugin System Extension enables your Power Modules application to support rich plugin architectures with type safety, automatic discovery, and full dependency injection support.*