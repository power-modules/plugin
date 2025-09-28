<?php

declare(strict_types=1);

namespace Example\TextProcessing\Plugin;

use Example\TextProcessing\Contract\TextProcessor;
use Modular\Plugin\PluginMetadata;

class ReversePlugin implements TextProcessor
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Reverse', '1.0.0', 'Reverses the string');
    }

    public function process(string $input): string
    {
        return strrev($input);
    }
}
