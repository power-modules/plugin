# API Reference

Concise reference for this package. See the framework for core types (App, containers, setups):
https://github.com/power-modules/framework

## Interfaces

### Plugin

```php
interface Plugin
{
    public static function getPluginMetadata(): PluginMetadata;
}
```

### PluginRegistry<TPlugin of Plugin>

```php
/** @template TPlugin of Plugin */
interface PluginRegistry
{
    /** @param class-string<TPlugin> $pluginClass */
    public function registerPlugin(string $pluginClass, \Psr\Container\ContainerInterface $container): void;

    /** @return class-string<TPlugin>[] */
    public function getRegisteredPlugins(): array;

    /** @param class-string<TPlugin> $pluginClass */
    public function hasPlugin(string $pluginClass): bool;

    /**
     * @param class-string<TPlugin> $pluginClass
     * @return TPlugin
     */
    public function makePlugin(string $pluginClass): Plugin;

    /**
     * Instantiate all registered plugins.
     *
     * @return list<TPlugin>
     */
    public function resolveAll(): array;

    // Note: Generics are for IDE/PHPStan only; the PHP return type remains Plugin at runtime.
    // Prefer assigning to the specific interface via PHPDoc when consuming.
}
```

### ProvidesPlugins<TPlugin of Plugin>

Marks a module that declares plugins to be registered into registries during setup.

```php
/** @template TPlugin of Plugin */
interface ProvidesPlugins
{
    /**
     * @return array<class-string<PluginRegistry<TPlugin>>, class-string<TPlugin>[]> 
     */
    public static function getPlugins(): array;
}
```

## Classes

### GenericPluginRegistry

Default registry storing (pluginClass → container) and resolving lazily.

```php
/** @implements PluginRegistry<TPlugin> */
class GenericPluginRegistry implements PluginRegistry
{
    public function registerPlugin(string $pluginClass, \Psr\Container\ContainerInterface $container): void;
    public function getRegisteredPlugins(): array; // class-string[]
    public function hasPlugin(string $pluginClass): bool;
    public function makePlugin(string $pluginClass): Plugin; // lazy DI resolution
    public function getPluginMetadataFor(string $pluginClass): PluginMetadata; // no instantiation
    public function listPluginMetadata(): array<class-string, PluginMetadata>; // discoverability
    public function resolveAll(): array; // instantiate all registered plugins
}
```

### PluginMetadata

Small readonly data object describing a plugin.

```php
final readonly class PluginMetadata
{
    public function __construct(
        public string $name,
        public string $version,
        public string $description = '',
    ) {}
}
```

## PowerModule Setup

These integrate with the framework’s setup phases.

### GenericPluginRegistrySetup (Post)

- Binds a single default `GenericPluginRegistry` in the ROOT container under `PluginRegistry::class` if no registry is present there yet.
- Runs after exports are registered, avoiding collisions with registries exported by modules.
- Does not bind into module containers.

### PluginRegistrySetup (Post)

- Finds modules implementing `ProvidesPlugins`
- Resolves the target registry in the ROOT container only; throws if not found
- Registers plugin classes with the registry, storing the module container for DI

Providing registries
- Default: `GenericPluginRegistrySetup` binds a shared default registry in ROOT under `PluginRegistry::class`.
- Custom: Export a custom registry from a module (so it appears in ROOT during Pre) or bind it programmatically to the root container before Post.

## Exceptions

- PluginRegistryNotFoundException — Target registry missing in ROOT container (registries are resolved from ROOT only)
- PluginNotRegisteredException — Attempt to create an unregistered plugin
- InvalidPluginImplementationException — Registration-time validation failed (class missing or not implementing Plugin)
- RuntimeException — Resolved instance does not implement `Plugin`

## Performance & Runtime Notes

- Registries store a map of plugin class → module container reference. This is small, but in long-lived workers the map persists for the process lifetime. Typically fine; just be aware when dynamically adding many plugins across modules.
