# Architecture

This guide explains how modules publish plugins and how registries resolve them. It follows the same two-phase setup model as the framework but focuses on the plugin-specific flow.

## Core Pieces

- Plugin: contract implemented by concrete plugins
- PluginRegistry<TPlugin>: type-safe registry for plugins
- GenericPluginRegistry: default registry implementation
- ProvidesPlugins<TPlugin>: marks a module that declares plugins
- PluginRegistrySetup: discovers and registers plugins from modules
- GenericPluginRegistrySetup: ensures a default registry is available
- PluginRegistryModuleConvenienceSetup: copies PluginRegistry into module containers for DI (optional convenience)

## Lifecycle at a Glance

The plugin system participates in the framework’s two-pass setup:

1) Pre phase
- Framework exports are registered to the root container (e.g., via ExportsComponentsSetup)

2) Post phase (PluginRegistrySetup)
- For each module implementing ProvidesPlugins, read getPlugins()
- For each [registry => pluginClasses] pair:
  - Resolve the target registry in the ROOT container
    - If missing, throw PluginRegistryNotFoundException
  - Register each plugin class in that registry with the module container reference

Additionally in Post phase: GenericPluginRegistrySetup
- If no registry is present in the root container, bind a single default `GenericPluginRegistry` under `PluginRegistry::class`.
- Runs after exports to avoid overriding a registry a module may have exported.

Also in Post phase: PluginRegistryModuleConvenienceSetup (convenience)
- Copies the `PluginRegistry::class` service definition from root into each module container if missing
- Purpose: allow modules to inject `PluginRegistry` without implementing `ImportsComponents`
- Root remains the source of truth; this mirrors the framework’s imports behavior without boilerplate

3) Runtime
- When makePlugin() is called, the registry asks the stored container to resolve the plugin class
- Full DI works as usual because containers are the source of truth
 - Note: The static generic type TPlugin is for IDE/PHPStan; at runtime the return type is `Plugin`.

## Resolution Priority

PluginRegistrySetup resolves registries from the ROOT container only.
If a registry is not found there, PluginRegistryNotFoundException is thrown.

How to provide registries to ROOT
- Default: `GenericPluginRegistrySetup` binds a shared default registry under `PluginRegistry::class` (Post phase).
- Custom: Export your registry from a module (so it’s registered to ROOT during Pre), or bind it programmatically before Post.

Module DI convenience
- When using `PluginRegistrySetup::withDefaults()`, `PluginRegistryModuleConvenienceSetup` makes `PluginRegistry` injectable within modules by copying the root definition into each module container. If a module provides its own binding or explicitly imports, its choice takes precedence.

## How it works (withDefaults ordering)

This describes only the order and intent of the default setup bundle, complementing the lifecycle above:

1) GenericPluginRegistrySetup (Post)
- Ensures a default registry exists in the root under `PluginRegistry::class` if none was provided/exported.

2) PluginRegistryModuleConvenienceSetup (Post)
- Copies the `PluginRegistry` definition from root into each module container if missing, so modules can inject it without `ImportsComponents`. Respects any module-local binding.

3) PluginRegistrySetup (Post)
- Discovers modules implementing `ProvidesPlugins` and registers their plugin classes into the resolved registries (from the root container). Assumes registries are already available from step 1 or from custom exports.

## Encapsulation & DI

- Registries don’t create plugins directly; they delegate to containers
- Each plugin is resolved from the module container that registered it
- Lazy loading ensures plugins are instantiated only when needed

## Error Modes

- PluginRegistryNotFoundException: Target registry is missing in both containers
- PluginNotRegisteredException: Attempting to create an unregistered plugin
- PluginAlreadyRegisteredException: Attempting to register the same plugin class twice in the same registry
- RuntimeException: Resolved instance does not implement Plugin
- InvalidPluginImplementationException: Thrown during registration when the provided class does not exist or does not implement the Plugin contract

## Simple Sequence Diagram

```
Module implements ProvidesPlugins
   → PluginRegistrySetup(Post) reads getPlugins()
     → Resolve registry (module → root → fail)
       → registry.registerPlugin(PluginClass, ModuleContainer)

At runtime:
   registry.makePlugin(PluginClass)
     → container.get(PluginClass)
       → returns Plugin instance (with DI)
```

That’s the whole flow. The design is intentionally small and predictable.

## Rationale: Root binding in Post phase

- Predictable access: `$app->get(PluginRegistry::class)` works by default.
- No collisions: module-exported registries are available before this runs, so we won’t override them.
- Clear scope: one default registry in root; modules can still bind or export specialized registries.

## Performance Notes

- The registry stores per-plugin container references to support lazy DI. Memory use scales with the number of registered plugins. In long-lived workers (e.g., RoadRunner/Swoole), this map persists for the process lifetime.
