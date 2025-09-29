# Use Case: Config‑Driven Plugin Pipelines

Assemble plugin pipelines from configuration (env/tenant-specific) by mapping config values to plugin class lists.

## Goal

- Define a `StepPlugin` contract
- Use autoprovisioned `GenericPluginRegistry`
- Implement a few steps
- Export the orchestrator from a core module; steps live in separate modules (3rd‑party friendly)
- Provide each step from its own module via `ProvidesPlugins` (3rd‑party friendly)
- Orchestrate them in order using the default `PluginRegistry`
- Drive which steps run from configuration

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

interface StepPlugin extends Plugin
{
    /** @param array<string,mixed> $payload */
    public function run(array $payload): array;
}

// Orchestrator that applies a sequence of steps
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

// 1) Core module: exports the orchestrator; does not declare plugins
final class PipelineCoreModule implements PowerModule, ExportsComponents
{
    public static function exports(): array
    {
        return [PipelineRunner::class];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        // Export orchestrator with the default PluginRegistry injected
        $c->set(PipelineRunner::class, PipelineRunner::class)
            ->addArguments([PluginRegistry::class]);
    }
}

// 2) Step plugins declared and registered from their own modules
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

final class StepAModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [StepA::class],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(StepA::class, StepA::class);
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

final class StepBModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [StepB::class],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(StepB::class, StepB::class);
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

final class StepCModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [StepC::class],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
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

## App Wiring

```php
declare(strict_types=1);

use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;

$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(
        PipelineCoreModule::class, // provides the orchestrator
        StepAModule::class,
        StepBModule::class,
        StepCModule::class,
    )
    ->build();

$config = require 'config.php';
$runner = $app->get(PipelineRunner::class);

$payload = ['input' => 42];
$outputDefault = $runner->runWithSteps($payload, $config['pipelines']['default']); // has A and B
$outputPremium = $runner->runWithSteps($payload, $config['pipelines']['premium']); // has A and C
```

Note: With `PluginRegistrySetup::withDefaults()`, a convenience setup makes `PluginRegistry` injectable inside modules. That’s why `PipelineRunner` can depend on `PluginRegistry` without adding `ImportsComponents`.

Additional Notes:
- Third‑party step modules (e.g., StepA, StepB, StepC) can live in separate repositories. They only depend on the shared `StepPlugin` contract and target the default `PluginRegistry` in `getPlugins()`.
- Only the core module should export the orchestrator. Plugin modules must not attempt to rebind the default registry; they simply declare their plugin classes against it.

## Edge Cases

- If config references a class that isn’t provided: `PluginNotRegisteredException`
- Missing pipeline key → fallback to `default` if present, else no-op
- Validate config shape at load time in production

## Acceptance Check

- Switching the pipeline name changes which steps run, with no code changes
- Invalid class in config results in a clear error at runtime
