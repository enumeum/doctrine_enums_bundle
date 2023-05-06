<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnumBundle\Command\DoctrineDecoration\Tools;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use function in_array;

class ArrayInputResolver
{
    /**
     * Symfony Proxy Command sets "em" as optional and assigns NULL to it,
     * but at the same time original Doctrine Command awaits "em" with value and fails.
     */
    private static iterable $filter = ['em'];

    public static function resolve(InputDefinition $definition, InputInterface $input): ArrayInput
    {
        $parameters = [];
        foreach ($input->getOptions() as $name => $value) {
            if ($definition->hasOption($name)) {
                if (null === $value && in_array($name, self::$filter, true)) {
                    continue;
                }
                if (null === $value && $definition->getOption($name)->isValueRequired()) {
                    continue;
                }

                $parameters['--' . $name] = $value;
            }
        }
        foreach ($input->getArguments() as $name => $value) {
            if ($definition->hasArgument($name)) {
                $parameters[$name] = $value;
            }
        }

        return new ArrayInput($parameters);
    }
}
