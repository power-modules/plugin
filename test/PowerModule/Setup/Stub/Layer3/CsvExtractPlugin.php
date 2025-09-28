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

namespace Modular\Plugin\Test\PowerModule\Setup\Stub\Layer3;

use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\PluginMetadata;
use Modular\Plugin\Test\PowerModule\Setup\Stub\Layer2\Extractor;

class CsvExtractPlugin implements Extractor, Plugin
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata(
            name: 'CSV Extraction Plugin',
            version: '1.0.0',
            description: 'A plugin to extract data from CSV files.',
        );
    }

    public function extract(string $resource): iterable
    {
        $handle = fopen($resource, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Could not open file: $resource");
        }

        while (($row = fgetcsv($handle)) !== false) {
            yield $row;
        }

        fclose($handle);
    }
}
