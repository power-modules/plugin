<?php

declare(strict_types=1);

namespace Example\TextProcessing;

use Example\TextProcessing\Contract\TextProcessor;
use Modular\Plugin\Contract\PluginRegistry;

class TextProcessingService
{
    /**
     * @param PluginRegistry<TextProcessor> $registry
     */
    public function __construct(private readonly PluginRegistry $registry)
    {
    }

    /**
     * @param array<class-string<TextProcessor>> $processorClasses
     */
    public function process(string $text, array $processorClasses): string
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
