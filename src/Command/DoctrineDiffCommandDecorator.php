<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnumBundle\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Exception\InvalidOptionUsage;
use Enumeum\DoctrineEnum\Type\TypeRegistryLoader;
use Enumeum\DoctrineEnumBundle\DefinitionRegistryCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DoctrineDiffCommandDecorator extends Command
{
    /** @var string|null */
    protected static $defaultName = 'doctrine:migrations:diff';

    public function __construct(
        private readonly DiffCommand $decorated,
        private readonly DefinitionRegistryCollection $definitionRegistryCollection,
        private readonly ?DependencyFactory $dependencyFactory = null,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription($this->decorated->getDescription());
        $this->setDefinition($this->decorated->getDefinition());
    }

    /**
     * @throws InvalidOptionUsage
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $registry = $this->definitionRegistryCollection->getRegistry(
            $this->dependencyFactory->getConfiguration()->getConnectionName() ?? 'default',
        );

        TypeRegistryLoader::load($registry->getDefinitions());

        $this->decorated->initialize($input, $output);

        return $this->decorated->execute($input, $output);
    }
}
