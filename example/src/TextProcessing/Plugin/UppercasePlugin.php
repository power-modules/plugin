<?php

declare(strict_types=1);

namespace Example\TextProcessing\Plugin;

use Example\TextProcessing\Contract\TextProcessor;
use Modular\Plugin\PluginMetadata;

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
