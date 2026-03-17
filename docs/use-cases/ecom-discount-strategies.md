# Use Case: E‑Commerce Discount Strategies

Compose stackable discount strategies (percentage, fixed, tiered) and apply them in a deterministic order.

## Goal

- Define a `DiscountStrategy` contract
- Implement `PercentageOff`, `FixedAmountOff`, and `TieredDiscount`
- Provide them via `ProvidesPlugins`
- Orchestrate composition in order with `PluginRegistry`

## Interfaces & Plugins

```php
declare(strict_types=1);

use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\PluginMetadata;

interface DiscountStrategy extends Plugin
{
    /** @param array{customerTier?:string,coupon?:string} $context */
    public function apply(float $subtotal, array $context = []): float; // returns new subtotal
}

final class PercentageOff implements DiscountStrategy
{
    public function __construct(private float $percent)
    {
        // 0..100
        $this->percent = max(0.0, min(100.0, $percent));
    }

    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Percentage Off', '1.0.0', 'Applies N% off the subtotal');
    }

    public function apply(float $subtotal, array $context = []): float
    {
        return max(0.0, $subtotal * (1 - $this->percent / 100));
    }
}

final class FixedAmountOff implements DiscountStrategy
{
    public function __construct(private float $amount)
    {
        $this->amount = max(0.0, $amount);
    }

    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Fixed Amount Off', '1.0.0', 'Subtracts a fixed amount');
    }

    public function apply(float $subtotal, array $context = []): float
    {
        return max(0.0, $subtotal - $this->amount);
    }
}

final class TieredDiscount implements DiscountStrategy
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Tiered Discount', '1.0.0', 'Bigger carts get bigger % off');
    }

    public function apply(float $subtotal, array $context = []): float
    {
        $rate = 0.0;
        if ($subtotal >= 200) {
            $rate = 0.15; // 15%
        } elseif ($subtotal >= 100) {
            $rate = 0.10; // 10%
        } elseif ($subtotal >= 50) {
            $rate = 0.05; // 5%
        }
        return max(0.0, $subtotal * (1 - $rate));
    }
}
```

Note: The constructor arguments for `PercentageOff` and `FixedAmountOff` would typically be injected with config values. For brevity, the module below wires concrete instances.

## Module

```php
declare(strict_types=1);

use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\Contract\PluginRegistry;

/** @implements ProvidesPlugins<DiscountStrategy> */
final class DiscountModule implements PowerModule, ProvidesPlugins, ExportsComponents
{
    public static function exports(): array
    {
        // Export the orchestrator so app and other modules can resolve it from root
        return [DiscountEngine::class];
    }

    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [
                PercentageOff::class,
                FixedAmountOff::class,
                TieredDiscount::class,
            ],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        // In real apps, use ServiceDefinition->addArguments() to inject values from config
        $c->set(PercentageOff::class, PercentageOff::class)->addArguments([10.0]); // 10% off
        $c->set(FixedAmountOff::class, FixedAmountOff::class)->addArguments([5.0]); // $5 off
        $c->set(TieredDiscount::class, TieredDiscount::class);

        // Bind the orchestrator and inject the PluginRegistry from root at resolve time
        $c->set(DiscountEngine::class, DiscountEngine::class)
            ->addArguments([PluginRegistry::class]);
    }
}

final class DiscountEngine
{
    /** @param PluginRegistry<DiscountStrategy> $registry */
    public function __construct(private PluginRegistry $registry) {}

    /**
     * @param array<class-string<DiscountStrategy>> $strategies
     * @param array{customerTier?:string,coupon?:string} $ctx
     */
    public function calculate(float $subtotal, array $strategies, array $ctx = []): float
    {
        $result = $subtotal;
        foreach ($strategies as $strategyClass) {
            $strategy = $this->registry->makePlugin($strategyClass);
            $result = $strategy->apply($result, $ctx);
        }
        return max(0.0, round($result, 2));
    }
}
```

## Orchestrator

Defined above next to the module as `DiscountEngine`.

## App Wiring

```php
declare(strict_types=1);

use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;
use Modular\Plugin\Contract\PluginRegistry;

$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(DiscountModule::class)
    ->build();

// Resolve the exported orchestrator from the root container
$engine = $app->get(DiscountEngine::class);

// Example: apply 10% off, then $5 off, then tiered
$final = $engine->calculate(120.00, [
    PercentageOff::class,
    FixedAmountOff::class,
    TieredDiscount::class,
]);
// Deterministic ordering; adjust to match business rules
```

Tip: If you prefer to keep the engine purely in your app layer (e.g., to inject app-specific config), you can instantiate it manually with the registry instead of exporting it.

Note: With `PluginRegistrySetup::withDefaults()`, `PluginRegistry` is also made injectable within modules via a convenience setup, so `DiscountEngine` can depend on it without requiring `ImportsComponents`.

## Metadata-driven UX

List available strategies without instantiation:

```php
foreach ($registry->listPluginMetadata() as $class => $meta) {
    echo $meta->name . ' — ' . $meta->description . "\n";
}
```

## Edge Cases

- Order matters; define a default order per your business rules
- Clamp totals to 0 to avoid negative results
- If a class isn’t registered, `PluginNotRegisteredException` is thrown

## Acceptance Check

- Given a subtotal and selected strategies, the engine returns the expected final price
- `listPluginMetadata()` lists available discounts with readable names
