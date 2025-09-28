#!/usr/bin/env php
<?php

declare(strict_types=1);

use Example\TextProcessing\TextPluginModule;
use Modular\Framework\App\Config\Config;
use Modular\Framework\App\Config\Setting;
use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->addPsr4('Example\\', __DIR__ . '/../src/');

$app = (new ModularAppBuilder(__DIR__ . '/..'))
    ->withConfig(Config::forAppRoot(__DIR__ . '/..')->set(Setting::CachePath, sys_get_temp_dir()))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(TextPluginModule::class)
    ->build();

/** @var PluginRegistry $registry */
$registry = $app->get(PluginRegistry::class);

fwrite(STDOUT, "Registries: default registry contents\n");
foreach ($registry->listPluginMetadata() as $class => $meta) {
    fwrite(STDOUT, sprintf("- %s: %s %s\n", $class, $meta->name, $meta->version ?? ''));
}

fwrite(STDOUT, "\nInstantiating all...\n");
foreach ($registry->resolveAll() as $i => $instance) {
    fwrite(STDOUT, sprintf("#%d %s\n", $i + 1, $instance::class));
}
