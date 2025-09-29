# Use Case: Plugin Catalog & Picker (Metadata-Driven)

A simple metadata-driven picker: list available tools using metadata, let users choose one, then resolve and run it.

## Goal

- Define a `Tool` contract
- Use autoprovisioned `GenericPluginRegistry`
- Implement `ReverseTool` and `UppercaseTool`
- Export the catalog service from a core module; tools live in separate modules (3rd‑party friendly)
- Provide each tool from its own module via `ProvidesPlugins` (3rd‑party friendly)
- Use metadata helpers without instantiating plugins to build a UI

## Modules

```php
declare(strict_types=1);

use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\PluginMetadata;

interface Tool extends Plugin
{
    public function run(string $input): string;
}

// Catalog orchestrator that uses metadata helpers
final class ToolCatalog
{
    /** @param PluginRegistry<Tool> $registry */
    public function __construct(private PluginRegistry $registry) {}

    /** @return array<class-string<Tool>, array{name:string,version:string,description:string}> */
    public function list(): array
    {
        $out = [];
        foreach ($this->registry->listPluginMetadata() as $class => $meta) {
            $out[$class] = [
                'name' => $meta->name,
                'version' => $meta->version,
                'description' => $meta->description,
            ];
        }
        return $out;
    }

    /** @param class-string<Tool> $class */
    public function pick(string $class): Tool
    {
        // Optional: validate existence via getPluginMetadataFor($class)
        $this->registry->getPluginMetadataFor($class);
        return $this->registry->makePlugin($class);
    }
}

// 1) Core module: exports the catalog; does not declare plugins
final class ToolCoreModule implements PowerModule, ExportsComponents
{
    public static function exports(): array
    {
        return [ToolCatalog::class];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        // Export catalog with the default PluginRegistry injected
        $c->set(ToolCatalog::class, ToolCatalog::class)
            ->addArguments([PluginRegistry::class]);
    }
}

// 2) Reverse tool module: declares and registers only ReverseTool
final class ReverseTool implements Tool
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Reverse', '1.0.0', 'Reverses the input string');
    }
    public function run(string $input): string { return strrev($input); }
}

final class ReverseToolModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [ReverseTool::class],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(ReverseTool::class, ReverseTool::class);
    }
}

// 3) Uppercase tool module: declares and registers only UppercaseTool
final class UppercaseTool implements Tool
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Uppercase', '1.0.0', 'Converts text to uppercase');
    }
    public function run(string $input): string { return strtoupper($input); }
}

final class UppercaseToolModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [UppercaseTool::class],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(UppercaseTool::class, UppercaseTool::class);
    }
}
```

## App Wiring

```php
declare(strict_types=1);

use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;

$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(
        ToolCoreModule::class, // provides the catalog
        ReverseToolModule::class,
        UppercaseToolModule::class,
    )
    ->build();

$catalog = $app->get(ToolCatalog::class);

$items = $catalog->list();
// render list to users and get their choice (class-string)
$chosenClass = array_key_first($items); // e.g., from UI selection
$tool = $catalog->pick($chosenClass);
$result = $tool->run('hello');
```

Note: With `PluginRegistrySetup::withDefaults()`, a convenience setup makes `PluginRegistry` injectable inside modules. That’s why `ToolCatalog` can depend on `PluginRegistry` without adding `ImportsComponents`.

Additional Notes:
- Use `listPluginMetadata()` to drive UI without instantiating plugins; optionally validate a user’s selection with `getPluginMetadataFor()` before resolving
- Third‑party tool modules (e.g., Reverse, Uppercase) can live in separate repositories. They only depend on the shared `Tool` contract and target the default `PluginRegistry` in `getPlugins()`.
- Only the core module should export the catalog. Plugin modules must not attempt to rebind the default registry; they simply declare their plugin classes against it.

## Edge Cases

- If a chosen class isn’t registered: `PluginNotRegisteredException` (picker should validate from metadata list first)
- Metadata conflicts (two plugins with the same name) are okay since selection is ultimately by class-string; consider displaying module/source context if needed

## Acceptance Check

- The catalog shows all registered tools with names/versions/descriptions
- Picking an item by class-string resolves and runs the correct plugin
