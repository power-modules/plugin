# Getting Started

Build an extensible plugin system in minutes. You’ll define a plugin, provide it from a module, and resolve it through a registry with full DI support.

Prerequisites:
- Power Modules Framework installed (see https://github.com/power-modules/framework)
- PHP 8.4+

## 1) Install

```sh
composer require power-modules/plugin
```

## 2) Define a plugin

```php
use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\PluginMetadata;

interface TextProcessor extends Plugin
{
    public function process(string $input): string;
}

final class UppercasePlugin implements TextProcessor
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Uppercase', '1.0.0', 'Converts text to uppercase');
    }

    public function process(string $input): string
    {
        return strtoupper($input);
    }
}
```

## 3) Provide plugins from a module

```php
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\Contract\PluginRegistry;

/**
 * @implements ProvidesPlugins<TextProcessor>
 */
final class TextPluginModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [UppercasePlugin::class],
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(UppercasePlugin::class, UppercasePlugin::class);
    }
}
```

`getPlugins()` declares which plugin classes should be registered into which registry. `register()` still has to bind those plugin classes in the module container so the registry can instantiate them later with full DI support.

## 4) Wire it in the app

```php
use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;
use Modular\Plugin\Contract\PluginRegistry;

$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(TextPluginModule::class)
    ->build();

/** @var PluginRegistry<TextProcessor> $registry */
$registry = $app->get(PluginRegistry::class);
$plugin = $registry->makePlugin(UppercasePlugin::class);

echo $plugin->process('hello'); // HELLO
```

## What you get

- Automatic discovery of plugins via module declarations
- Lazy instantiation with dependency injection
- Type-safe, generic registry API

Note: When using `PluginRegistrySetup::withDefaults()`, the registry is provided in the root container and also made injectable within modules as a convenience, so you can constructor-inject `PluginRegistry` in your services without extra imports.

Next: Learn how it works under the hood in [Architecture](architecture.md).
