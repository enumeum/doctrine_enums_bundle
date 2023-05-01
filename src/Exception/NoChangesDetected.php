<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnumBundle\Exception;

use Doctrine\Migrations\Generator\Exception\GeneratorException;
use RuntimeException;

final class NoChangesDetected extends RuntimeException implements GeneratorException
{
    public static function new(): self
    {
        return new self('No changes detected in your enums mapping.');
    }
}
