<?php

declare(strict_types=1);

namespace Example\TextProcessing\Contract;

use Modular\Plugin\Contract\Plugin;

interface TextProcessor extends Plugin
{
    public function process(string $input): string;
}
