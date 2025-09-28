<?php

/**
 * This file is part of the Plugin extension for the Modular Framework.
 *
 * (c) 2025 Evgenii Teterin
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Modular\Plugin\Contract;

use Modular\Plugin\PluginMetadata;

interface Plugin
{
    public static function getPluginMetadata(): PluginMetadata;
}
