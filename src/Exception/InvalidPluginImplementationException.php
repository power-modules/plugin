<?php

declare(strict_types=1);

namespace Modular\Plugin\Exception;

use RuntimeException;

class InvalidPluginImplementationException extends RuntimeException
{
    public function __construct(
        private readonly string $registryClass,
        private readonly string $pluginClass,
        ?string $reason = null,
    ) {
        $message = sprintf(
            'Invalid plugin implementation: %s (expected to implement %s). Registry: %s%s',
            $pluginClass,
            \Modular\Plugin\Contract\Plugin::class,
            $registryClass,
            $reason ? sprintf(' — %s', $reason) : '',
        );

        parent::__construct($message);
    }

    public function getRegistryClass(): string
    {
        return $this->registryClass;
    }

    public function getPluginClass(): string
    {
        return $this->pluginClass;
    }
}
