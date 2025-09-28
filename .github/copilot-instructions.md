````instructions
# Plugin System Extension - AI Coding Agent Instructions

This document provides guidance for AI coding agents to effectively contribute to the Plugin System Extension for the Power Modules framework.

## Big Picture Architecture

The Plugin System Extension is a **type-safe plugin architecture library** for the Power Modules framework that enables applications to support extensible plugin systems with automatic discovery, dependency injection, and seamless integration. It's designed for applications that need third-party extensions, dynamic feature loading, or modular plugin architectures.

### Core Concepts

- **Plugin Interface (`Plugin`):** The foundational contract requiring `getPluginMetadata()` method that all plugins must implement. Provides plugin identity and metadata information.
- **Plugin Registry (`PluginRegistry<TPlugin>`):** Generic, type-safe interface for managing plugin instances with container-based resolution. Uses `@template TPlugin of Plugin` for compile-time type safety.
- **Plugin Providers (`ProvidesPlugins<TPlugin>`):** PowerModule interface for declaring plugins through `getPlugins()` method, enabling automatic discovery by PowerModuleSetup.
- **PowerModule Integration:** Seamless integration with Power Modules' PowerModuleSetup extension system through `PluginRegistrySetup`.
- **Generic Type System:** All core interfaces use PHP generics with `@template` annotations for type-safe plugin handling.
- **Container-Based Resolution:** Plugins are resolved via PSR-11 containers enabling lazy loading and full dependency injection support.

### Plugin System Lifecycle

**Phase 1: Module Registration**
```php
$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(TextPluginModule::class)
    ->build();
```

What happens:
1. `GenericPluginRegistrySetup` creates/binds the default `GenericPluginRegistry` in the ROOT container (if none is provided)
2. `PluginRegistryModuleConvenienceSetup` copies the ROOT `PluginRegistry` binding into each module container (if missing) for DI ergonomics
3. Modules are registered with their containers
4. `PluginRegistrySetup` scans modules implementing `ProvidesPlugins` and registers declared plugins into the targeted registry

**Phase 2: Plugin Discovery**
```php
/**
 * @implements ProvidesPlugins<TextProcessor>
 */
class TextPluginModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [UppercasePlugin::class, ReversePlugin::class],
        ];
    }
}
```

What happens:
1. `PluginRegistrySetup` calls `getPlugins()` on each module
2. Plugin classes are registered with their module container
3. Registry stores container references, not plugin instances

**Phase 3: Plugin Resolution**
```php
$plugin = $registry->makePlugin(UppercasePlugin::class);
```

What happens:
1. Registry retrieves the container for the plugin class
2. Container resolves the plugin with full dependency injection
3. Plugin instance is returned (lazy loading)

### Plugin Architecture Patterns

**Basic Plugin Pattern:**
```php
interface TextProcessor extends Plugin
{
    public function process(string $input): string;
}

class UppercasePlugin implements TextProcessor
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

**Plugin Provider Module:**
```php
/**
 * @implements ProvidesPlugins<TextProcessor>
 */
class TextPluginModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [UppercasePlugin::class, ReversePlugin::class],
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(UppercasePlugin::class, UppercasePlugin::class);
        $container->set(ReversePlugin::class, ReversePlugin::class);
    }
}
```

**Plugin Service Pattern:**
```php
class TextProcessingService
{
    /**
     * @param PluginRegistry<TextProcessor> $registry
     */
    public function __construct(private PluginRegistry $registry) {}

    public function processText(string $text, array $processorClasses): string
    {
        $result = $text;
        foreach ($processorClasses as $processorClass) {
            /** @var TextProcessor $processor */
            $processor = $this->registry->makePlugin($processorClass);
            $result = $processor->process($result);
        }
        return $result;
    }
}
```

**Plugin with Dependencies:**
```php
class LoggingUppercasePlugin implements TextProcessor
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function process(string $input): string
    {
        $this->logger->log("Processing: {$input}");
        $result = strtoupper($input);
        $this->logger->log("Result: {$result}");
        return $result;
    }

    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Logging Uppercase', '1.0.0');
    }
}

// Module registration with dependency injection
$container->set(LoggingUppercasePlugin::class, LoggingUppercasePlugin::class)
    ->addArguments([LoggerInterface::class]);
```

## Key Features for AI Development

When working with the plugin system, keep these architectural benefits in mind:

- **Enhanced Developer Experience**: Generic annotations provide better IDE support and static analysis
- **Automatic Discovery**: PowerModuleSetup scans modules for plugin declarations
- **Lazy Loading**: Plugins are instantiated only when requested through registries
- **Container Integration**: Full dependency injection support for plugins
- **Multiple Registries**: Support specialized plugin types with custom registries
- **Framework Native**: Built specifically for Power Modules architecture
- **Team Scalability**: Different teams can develop plugins independently
- **Encapsulation**: Plugins maintain proper module boundaries
- **Metadata Helpers**: Registries expose `getPluginMetadataFor()` and `listPluginMetadata()` for discovery without instantiation

## Registry Patterns

### Single Registry Pattern
All plugins managed by one registry:
```php
$registry = $app->get(PluginRegistry::class);
$plugins = array_map([$registry, 'makePlugin'], $registry->getRegisteredPlugins());
```

### Multiple Registry Pattern
Specialized registries for different plugin categories:
```php
class ValidationPluginRegistry extends GenericPluginRegistry {}
class TransformationPluginRegistry extends GenericPluginRegistry {}

/**
 * @implements ProvidesPlugins<ValidationPlugin&TransformationPlugin>
 */
class MultiRegistryModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            ValidationPluginRegistry::class => [EmailValidator::class],
            TransformationPluginRegistry::class => [DataTransformer::class],
        ];
    }
}
```

### Registry Service Pattern
Services that orchestrate multiple plugins:
```php
class PluginOrchestrator
{
    public function __construct(
        private PluginRegistry $textProcessorRegistry,
        private PluginRegistry $validatorRegistry,
    ) {}

    public function processAndValidate(string $input): string
    {
        // Use validation plugins first
        foreach ($this->validatorRegistry->getRegisteredPlugins() as $validatorClass) {
            $validator = $this->validatorRegistry->makePlugin($validatorClass);
            if (!$validator->validate($input)) {
                throw new InvalidInputException();
            }
        }
        
        // Then process with transformation plugins
        $result = $input;
        foreach ($this->textProcessorRegistry->getRegisteredPlugins() as $processorClass) {
            $processor = $this->textProcessorRegistry->makePlugin($processorClass);
            $result = $processor->process($result);
        }
        
        return $result;
    }
}
```

## Development Commands

```bash
# Run tests with color output
make test

# Code style check (uses .php-cs-fixer.php config)
make codestyle

# Static analysis (PHPStan level 8)
make phpstan

# Build dev container
make devcontainer
```

## Code Standards

- **PHP 8.4** minimum with strict types: `declare(strict_types=1);`
- **PSR-12** + custom rules via php-cs-fixer (see `.php-cs-fixer.php`)
- **PHPStan level 8** - maximum strictness
- **Readonly classes** preferred for data objects (see `PluginMetadata`)
- **Template generics** for type-safe plugin handling

## Critical Implementation Details

### PowerModule Setup Integration
Always use `PluginRegistrySetup::withDefaults()` for complete setup:
```php
$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(...$modules)
    ->build();
```
This provides three setups in this exact order (Post phase):
1) `GenericPluginRegistrySetup` (binds default registry in ROOT if absent)
2) `PluginRegistryModuleConvenienceSetup` (copies ROOT `PluginRegistry` binding into module containers if missing)
3) `PluginRegistrySetup` (discovers and registers plugins to registries)

### Registry Resolution Policy
`PluginRegistrySetup` resolves registries from the ROOT container only (single source of truth). If the ROOT container lacks the requested registry class, it throws `PluginRegistryNotFoundException`.

### Plugin Registry Targeting
- Use `PluginRegistry::class` to target the default `GenericPluginRegistry` (recommended)
- Use `GenericPluginRegistry::class` for explicit generic registry targeting
- Custom registries must be available in the ROOT container before `PluginRegistrySetup` runs (export them via a module or wire them programmatically)
- Convenience DI copying applies only to the default `PluginRegistry` binding. Custom registries are not automatically copied into module containers—you must export/import or bind them explicitly where needed.

### Container-Based Plugin Resolution
Plugins are resolved via PSR-11 containers - the registry stores the container reference, not the plugin instance. This enables:
- Lazy loading of plugin instances
- Full dependency injection support for plugins
- Proper module encapsulation and boundaries

## Key Components and Directories

- `src/Contract/`: Core interfaces (`Plugin`, `PluginRegistry`, `ProvidesPlugins`)
- `src/PowerModule/Setup/`: Framework integration classes (`GenericPluginRegistrySetup`, `PluginRegistryModuleConvenienceSetup`, `PluginRegistrySetup`)
- `src/Exception/`: Domain-specific exceptions (`PluginNotRegisteredException`, `PluginRegistryNotFoundException`)
- `src/GenericPluginRegistry.php`: Default registry implementation with container-based resolution
- `src/PluginMetadata.php`: Readonly data object for plugin information
- `test/`: PHPUnit tests with namespace `Modular\Plugin\Test\`, includes `AbstractPluginRegistryTest` for testing patterns

## Testing Conventions

- Tests extend `PHPUnit\Framework\TestCase` and use constructor mocks for container testing
- Use `AbstractPluginRegistryTest` as a pattern for testing plugin registries
- Test plugin modules with `ModularAppBuilder` and temporary cache paths
- Verify plugin registration: use `$registry->hasPlugin(PluginClass::class)` to test availability
- Test plugin creation: use `$registry->makePlugin(PluginClass::class)` with proper mocking
- Test plugin metadata: verify `getPluginMetadata()` returns correct information

```php
class MyPluginTest extends TestCase
{
    public function testPluginRegistration(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(MyPlugin::class)
            ->willReturn(new MyPlugin());

        $registry = new GenericPluginRegistry();
        $registry->registerPlugin(MyPlugin::class, $container);
        
        self::assertTrue($registry->hasPlugin(MyPlugin::class));
        
        $plugin = $registry->makePlugin(MyPlugin::class);
        self::assertInstanceOf(MyPlugin::class, $plugin);
    }
}
```

Integration testing pattern:
```php
public function testPluginModuleIntegration(): void
{
    $app = (new ModularAppBuilder(__DIR__))
        ->withConfig(Config::forAppRoot(__DIR__)->set(Setting::CachePath, sys_get_temp_dir()))
        ->withPowerSetup(...PluginRegistrySetup::withDefaults())
        ->withModules(TextPluginModule::class)
        ->build();

    $registry = $app->get(PluginRegistry::class);
    self::assertTrue($registry->hasPlugin(UppercasePlugin::class));
}
```

## Developer Workflows

The project uses a `Makefile` for common development tasks:

```bash
make test         # Run PHPUnit tests with color output (no coverage)
make codestyle    # Check PHP CS Fixer compliance
make phpstan      # Run static analysis with PHPStan level 8
make devcontainer # Build development container
```

## Code Conventions

- **Strict Types:** All files use `declare(strict_types=1);`
- **PHP 8.4+:** Modern PHP features utilized throughout
- **PSR Standards:** PSR-4 autoloading, PSR-11 container interoperability
- **PSR-12 + Custom Rules:** Code style via php-cs-fixer (see `.php-cs-fixer.php`)
- **PHPStan Level 8:** Maximum static analysis strictness
- **Readonly Classes:** Preferred for data objects (see `PluginMetadata`)
- **Template Generics:** Use `@template` annotations for type-safe plugin handling
- **Interface-First Design:** Components are typically defined by interfaces first
- **Constructor Injection:** Preferred for dependency injection via `ServiceDefinition::addArguments()`

## Performance Considerations

### Lazy Loading
Plugins are created only when requested:
```php
// Registration happens at setup time (cheap)
$registry->registerPlugin(ExpensivePlugin::class, $container);

// Plugin creation happens on first access (lazy)
$plugin = $registry->makePlugin(ExpensivePlugin::class); // Created here with full DI
```

### Container Resolution Impact
Plugin dependencies are resolved through PSR-11 containers with full framework integration:
```php
class DatabasePlugin implements Plugin
{
    public function __construct(
        private PDO $database,           // Resolved from container
        private LoggerInterface $logger, // Resolved from container
        private ConfigInterface $config, // Resolved from container
    ) {}
}
```

## Security Considerations

### Plugin Validation
Always validate plugin implementations in production:
```php
public function makePlugin(string $pluginClass): Plugin
{
    $plugin = $this->container->get($pluginClass);
    
    if (!$plugin instanceof Plugin) {
        throw new \RuntimeException("Invalid plugin implementation");
    }
    
    return $plugin;
}
```

### Container Isolation
Each module has its own container, preventing plugin cross-contamination and maintaining proper encapsulation boundaries.

When adding new features or fixing bugs, ensure that plugins follow the type-safe generic patterns and that all plugin registrations go through the PowerModuleSetup system for consistency and proper container integration.