<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnumBundle;

use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistryLoader;

class DefinitionRegistryCollection
{
    public function __construct(
        /** @var iterable<DefinitionRegistryLoader> $loaders */
        private iterable $loaders = [],
    ) {
    }

    public function addLoader(string $name, DefinitionRegistryLoader $loader): void
    {
        $this->loaders[$name] = $loader;
    }

    public function getRegistry(string $name): DefinitionRegistry
    {
        $loader = $this->loaders[$name];

        return $loader->getRegistry();
    }
}
