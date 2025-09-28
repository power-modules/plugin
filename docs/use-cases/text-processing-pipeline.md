# Use Case: Text Processing Pipeline

A small service that chains multiple text processors (plugins) to transform input step-by-step.

## Goal

- Define a `TextProcessor` contract
- Implement several plugins
- Provide them from a module via `ProvidesPlugins`
- Resolve and orchestrate them through `PluginRegistry`

## Interfaces & Plugins

```php
use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\PluginMetadata;

interface TextProcessor extends Plugin
{
    public function process(string $input): string;
}

final class TrimPlugin implements TextProcessor
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Trim', '1.0.0', 'Trims whitespace');
    }

    public function process(string $input): string
    {
        return trim($input);
    }
}

final class UppercasePlugin implements TextProcessor
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Uppercase', '1.0.0', 'Converts to uppercase');
    }

    public function process(string $input): string
    {
        return strtoupper($input);
    }
}
```

## Module

```php
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\Contract\PluginRegistry;

/** @implements ProvidesPlugins<TextProcessor> */
final class TextPluginModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [TrimPlugin::class, UppercasePlugin::class],
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(TrimPlugin::class, TrimPlugin::class);
        $container->set(UppercasePlugin::class, UppercasePlugin::class);
    }
}
```

## Orchestrator Service

```php
use Modular\Plugin\Contract\PluginRegistry;

final class TextProcessingService
{
    /** @param PluginRegistry<TextProcessor> $registry */
    public function __construct(private PluginRegistry $registry) {}

    /** @param array<class-string<TextProcessor>> $processorClasses */
    public function process(string $text, array $processorClasses): string
    {
        $result = $text;
        foreach ($processorClasses as $processorClass) {
            $processor = $this->registry->makePlugin($processorClass);
            $result = $processor->process($result);
        }
        return $result;
    }
}
```

## App Wiring

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

$service = new TextProcessingService($registry);
$output = $service->process("  hello  ", [TrimPlugin::class, UppercasePlugin::class]);
// $output === 'HELLO'
```

This pattern scales naturally: add plugins, register them, and orchestrate them any way you like.
