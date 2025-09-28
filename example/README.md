# Plugin System Example

This example shows how to wire the Plugin extension into a small app using the Power Modules framework. It demonstrates:

- Defining a plugin interface and two plugins
- Providing plugins from a module via ProvidesPlugins
- Exporting a service orchestrator via ExportsComponents
- Using PluginRegistrySetup::withDefaults() (including the module DI convenience)

## Run it

- Prerequisite: composer dependencies are already installed at the repo root.
- Execute the example runner:

```sh
php example/bin/run.php
```

You should see the pipeline apply two text processors (uppercase, reverse) and print the results, plus a metadata listing.
