# Use Case: E‑Commerce Payment Gateway Adapters

Implement multiple payment gateway adapters (Stripe, PayPal, …) with a custom registry and DI-managed dependencies.

## Goal

- Define a `PaymentGateway` contract
- Create a custom `PaymentGatewayRegistry`
- Implement `StripeGateway` and `PaypalGateway` with HTTP client and logger dependencies
- Provide adapters from a module; orchestrate via the custom registry

## Interfaces & Plugins

```php
declare(strict_types=1);

use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\PluginMetadata;
use Psr\Http\Client\ClientInterface; // PSR-18 HTTP client
use Psr\Log\LoggerInterface;

interface PaymentGateway extends Plugin
{
    /** @param array{customerId?:string,meta?:array<string,mixed>} $context */
    public function charge(int $amountCents, string $currency, array $context = []): string; // returns transactionId
}

final class StripeGateway implements PaymentGateway
{
    public function __construct(private ClientInterface $http, private LoggerInterface $logger) {}

    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Stripe', '1.0.0', 'Stripe payment adapter');
    }

    public function charge(int $amountCents, string $currency, array $context = []): string
    {
        $this->logger->info('Charging via Stripe', ['amount' => $amountCents, 'currency' => $currency]);
        // Simulate request; replace with real API call using $this->http
        return 'tx_stripe_' . bin2hex(random_bytes(4));
    }
}

final class PaypalGateway implements PaymentGateway
{
    public function __construct(private ClientInterface $http, private LoggerInterface $logger) {}

    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('PayPal', '1.0.0', 'PayPal payment adapter');
    }

    public function charge(int $amountCents, string $currency, array $context = []): string
    {
        $this->logger->info('Charging via PayPal', ['amount' => $amountCents, 'currency' => $currency]);
        return 'tx_paypal_' . bin2hex(random_bytes(4));
    }
}
```

## Custom Registry

```php
declare(strict_types=1);

use Modular\Plugin\GenericPluginRegistry;

/** @extends GenericPluginRegistry<PaymentGateway> */
final class PaymentGatewayRegistry extends GenericPluginRegistry
{
    // Marker type for clarity and DI binding; behavior inherits from GenericPluginRegistry
}
```

## Module

```php
declare(strict_types=1);

use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Plugin\Contract\ProvidesPlugins;

/** @implements ProvidesPlugins<PaymentGateway> */
final class PaymentModule implements PowerModule, ProvidesPlugins, ExportsComponents
{
    public static function exports(): array
    {
        return [CheckoutService::class, PaymentGatewayRegistry::class];
    }
    public static function getPlugins(): array
    {
        return [
            PaymentGatewayRegistry::class => [StripeGateway::class, PaypalGateway::class],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        // In real apps, bind a PSR-18 client and PSR-3 logger first
        $c->set(StripeGateway::class, StripeGateway::class) /* ->addArguments([ClientInterface::class, LoggerInterface::class]) */;
        $c->set(PaypalGateway::class, PaypalGateway::class) /* ->addArguments([ClientInterface::class, LoggerInterface::class]) */;

        // Bind the custom registry so PluginRegistrySetup can find it in the root container
        $c->set(PaymentGatewayRegistry::class, new PaymentGatewayRegistry());

        // Export the checkout service with the custom registry injected
        $c->set(CheckoutService::class, CheckoutService::class)
            ->addArguments([PaymentGatewayRegistry::class]);
    }
}
```

## Orchestrator

```php
declare(strict_types=1);

final class CheckoutService
{
    public function __construct(private PaymentGatewayRegistry $registry) {}

    /** @param class-string<PaymentGateway> $gateway */
    public function pay(int $amountCents, string $currency, string $gateway, array $ctx = []): string
    {
        $adapter = $this->registry->makePlugin($gateway);
        return $adapter->charge($amountCents, $currency, $ctx);
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
    ->withModules(PaymentModule::class)
    ->build();

$checkout = $app->get(CheckoutService::class);
$tx = $checkout->pay(1999, 'USD', StripeGateway::class, ['customerId' => 'cus_123']);
```

## Edge Cases

- Unknown gateway → `PluginNotRegisteredException`
- Network failures and API errors should be mapped to domain-specific exceptions
- Ensure the custom registry is bound in the root container; otherwise setup will throw a helpful error

Note: The convenience setup that copies `PluginRegistry` into module containers applies to the default registry only. For custom registries like `PaymentGatewayRegistry`, continue to export/import explicitly and inject them where needed.

## Acceptance Check

- Selecting a configured gateway processes a payment and returns a transaction id (simulated)
- Registry discovery fails fast with a clear error if `PaymentGatewayRegistry` isn’t bound
