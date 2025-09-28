#!/usr/bin/env php
<?php

declare(strict_types=1);

use Example\TextProcessing\Contract\TextProcessor;
use Example\TextProcessing\Plugin\ReversePlugin;
use Example\TextProcessing\Plugin\UppercasePlugin;
use Example\TextProcessing\TextPluginModule;
use Example\TextProcessing\TextProcessingService;
use Modular\Framework\App\Config\Config;
use Modular\Framework\App\Config\Setting;
use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->addPsr4('Example\\', __DIR__ . '/../src/');

// Build the app with the Plugin setups and our module
$app = (new ModularAppBuilder(__DIR__ . '/..'))
    ->withConfig(Config::forAppRoot(__DIR__ . '/..')->set(Setting::CachePath, sys_get_temp_dir()))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(TextPluginModule::class)
    ->build();

$service = $app->get(TextProcessingService::class);

$input = 'Hello Plugins';
$steps = [UppercasePlugin::class, ReversePlugin::class];

$result = $service->process($input, $steps);

// Show the pipeline result
fwrite(STDOUT, "Input:  $input\n");
fwrite(STDOUT, "Result: $result\n\n");

// Also demonstrate metadata helpers via the convenience-injected registry
/** @var PluginRegistry<TextProcessor> $registry */
$registry = $app->get(PluginRegistry::class);
$metadataMap = $registry->listPluginMetadata();

fwrite(STDOUT, "Registered plugins and metadata:\n");
foreach ($metadataMap as $class => $meta) {
    fwrite(STDOUT, sprintf("- %s: %s %s — %s\n", $class, $meta->name, $meta->version ?? '', $meta->description ?? ''));
}
