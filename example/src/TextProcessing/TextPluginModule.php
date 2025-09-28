<?php

declare(strict_types=1);

namespace Example\TextProcessing;

use Example\TextProcessing\Contract\TextProcessor;
use Example\TextProcessing\Plugin\ReversePlugin;
use Example\TextProcessing\Plugin\UppercasePlugin;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\Contract\ProvidesPlugins;

/**
 * @implements ProvidesPlugins<TextProcessor>
 */
class TextPluginModule implements PowerModule, ProvidesPlugins, ExportsComponents
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [UppercasePlugin::class, ReversePlugin::class],
        ];
    }

    public static function exports(): array
    {
        // Export our orchestrator/service so app->get(TextProcessingService::class) works
        return [TextProcessingService::class];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(UppercasePlugin::class, UppercasePlugin::class);
        $container->set(ReversePlugin::class, ReversePlugin::class);
        $container->set(TextProcessingService::class, TextProcessingService::class)
            ->addArguments([PluginRegistry::class]);
    }
}
