# Use Case: Plugin Catalog & Picker (Metadata-Driven)

Goal
- Build a simple "plugin catalog" for users to select a plugin based on human-friendly metadata (name/description/version), then resolve and run it.

What it shows
- Using `listPluginMetadata()` and `getPluginMetadataFor()` without instantiating plugins
- Mapping UI selection back to plugin class strings
- Keeping orchestration and UI concerns separate

Contracts (skeleton)
```php
use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\PluginMetadata;

interface Tool extends Plugin
{
    public function run(string $input): string;
}

final class ReverseTool implements Tool
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Reverse', '1.0.0', 'Reverses the input string');
    }
    public function run(string $input): string { return strrev($input); }
}

final class UppercaseTool implements Tool
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Uppercase', '1.0.0', 'Converts text to uppercase');
    }
    public function run(string $input): string { return strtoupper($input); }
}
```

Module (skeleton)
```php
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\Contract\PluginRegistry;

/** @implements ProvidesPlugins<Tool> */
final class ToolModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [PluginRegistry::class => [ReverseTool::class, UppercaseTool::class]];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(ReverseTool::class, ReverseTool::class);
        $c->set(UppercaseTool::class, UppercaseTool::class);
    }
}
```

Catalog service (uses metadata helpers)
```php
use Modular\Plugin\Contract\PluginRegistry;

/** @template T of Tool */
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
```

App wiring (skeleton)
```php
use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;
use Modular\Plugin\Contract\PluginRegistry;

$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(ToolModule::class)
    ->build();

/** @var PluginRegistry<Tool> $registry */
$registry = $app->get(PluginRegistry::class);
$catalog = new ToolCatalog($registry);

$items = $catalog->list();
// render list to users and get their choice (class-string)
$chosenClass = array_key_first($items); // e.g., from UI selection
$tool = $catalog->pick($chosenClass);
$result = $tool->run('hello');
```

Edge cases
- If a chosen class isn’t registered: `PluginNotRegisteredException` (picker should validate from metadata list first)
- Metadata conflicts (two plugins with the same name) are okay since selection is ultimately by class-string; consider displaying module/source context if needed

Acceptance criteria
- The catalog shows all registered tools with names/versions/descriptions
- Picking an item by class-string resolves and runs the correct plugin
