# Use Case: Config‑Driven Plugin Pipelines

Assemble plugin pipelines from configuration (env/tenant-specific) by mapping config values to plugin class lists.

## Goal

- Define a `StepPlugin` contract
- Implement a few steps
- Provide them via `ProvidesPlugins`
- Drive which steps run from configuration

## Interfaces & Plugins

```php
declare(strict_types=1);

use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\PluginMetadata;

interface StepPlugin extends Plugin
{
    /** @param array<string,mixed> $payload */
    public function run(array $payload): array;
}

final class StepA implements StepPlugin
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Step A', '1.0.0', 'Adds flag A');
    }

    public function run(array $payload): array
    {
        $payload['A'] = true;
        return $payload;
    }
}

final class StepB implements StepPlugin
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Step B', '1.0.0', 'Adds flag B');
    }

    public function run(array $payload): array
    {
        $payload['B'] = true;
        return $payload;
    }
}

final class StepC implements StepPlugin
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Step C', '1.0.0', 'Adds flag C');
    }

    public function run(array $payload): array
    {
        $payload['C'] = true;
        return $payload;
    }
}
```

## Module

```php
declare(strict_types=1);

use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\Contract\PluginRegistry;

/** @implements ProvidesPlugins<StepPlugin> */
final class PipelineModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [PluginRegistry::class => [StepA::class, StepB::class, StepC::class]];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(StepA::class, StepA::class);
        $c->set(StepB::class, StepB::class);
        $c->set(StepC::class, StepC::class);
    }
}
```

## Config mapping

```php
// config.php
return [
    'pipelines' => [
        'default' => [StepA::class, StepB::class],
        'premium' => [StepA::class, StepC::class],
    ],
];
```

## Runner

```php
declare(strict_types=1);

use Modular\Plugin\Contract\PluginRegistry;

final class PipelineRunner
{
    /** @param PluginRegistry<StepPlugin> $registry */
    public function __construct(private PluginRegistry $registry) {}

    /**
     * @param array<class-string<StepPlugin>> $steps
     * @return array<string,mixed>
     */
    public function runWithSteps(array $payload, array $steps): array
    {
        foreach ($steps as $stepClass) {
            $step = $this->registry->makePlugin($stepClass);
            $payload = $step->run($payload);
        }
        return $payload;
    }
}
```

## App Wiring

```php
declare(strict_types=1);

use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Framework\PowerModule\Contract\ExportsComponents;

// Export the runner from the module
/** @implements ProvidesPlugins<StepPlugin> */
final class PipelineModule implements PowerModule, ProvidesPlugins, ExportsComponents
{
    public static function exports(): array
    {
        return [PipelineRunner::class];
    }

    public static function getPlugins(): array
    {
        return [PluginRegistry::class => [StepA::class, StepB::class, StepC::class]];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(StepA::class, StepA::class);
        $c->set(StepB::class, StepB::class);
        $c->set(StepC::class, StepC::class);
        $c->set(PipelineRunner::class, PipelineRunner::class)->addArguments([PluginRegistry::class]);
    }
}

$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(PipelineModule::class)
    ->build();

$config = require 'config.php';
$runner = $app->get(PipelineRunner::class);

$payload = ['input' => 42];
$outputDefault = $runner->runWithSteps($payload, $config['pipelines']['default']); // has A and B
$outputPremium = $runner->runWithSteps($payload, $config['pipelines']['premium']); // has A and C
```

Note: With `PluginRegistrySetup::withDefaults()`, a convenience setup makes `PluginRegistry` injectable inside modules. That’s why `PipelineRunner` can depend on `PluginRegistry` without adding `ImportsComponents`.

## Edge Cases

- If config references a class that isn’t provided: `PluginNotRegisteredException`
- Missing pipeline key → fallback to `default` if present, else no-op
- Validate config shape at load time in production

## Acceptance Check

- Switching the pipeline name changes which steps run, with no code changes
- Invalid class in config results in a clear error at runtime
