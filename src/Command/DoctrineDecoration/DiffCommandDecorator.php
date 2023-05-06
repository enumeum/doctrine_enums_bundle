<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnumBundle\Command\DoctrineDecoration;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand as DoctrineDiffCommand;
use Enumeum\DoctrineEnum\Type\TypeRegistryLoader;
use Enumeum\DoctrineEnumBundle\Command\DiffCommand as EnumeumDiffCommand;
use Enumeum\DoctrineEnumBundle\Command\DoctrineDecoration\Tools\ArrayInputResolver;
use Enumeum\DoctrineEnumBundle\DefinitionRegistryCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class DiffCommandDecorator extends Command
{
    /** @var string|null */
    protected static $defaultName = 'doctrine:migrations:diff';

    public function __construct(
        private readonly DoctrineDiffCommand $doctrineCommand,
        private readonly EnumeumDiffCommand $enumeumCommand,
        private readonly DefinitionRegistryCollection $definitionRegistryCollection,
        private readonly ?DependencyFactory $dependencyFactory = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setApplication($this->doctrineCommand->getApplication());
        $this->setAliases($this->doctrineCommand->getAliases());
        $this->setDescription($this->doctrineCommand->getDescription());
        $this->setDefinition($this->doctrineCommand->getDefinition());

        $this
            ->addOption(
                'with-enums',
                'E',
                InputOption::VALUE_NONE,
                'Run Enumeum diff command before the general one to create a migration with enums.',
            )
            ->addOption(
                'ignore-unknown-enums',
                'U',
                InputOption::VALUE_NONE,
                'Do not sync database enum types which do not defined in application.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $registry = $this->definitionRegistryCollection->getRegistry(
            $this->dependencyFactory->getConfiguration()->getConnectionName() ?? 'default',
        );
        TypeRegistryLoader::load($registry->getDefinitions());

        $enumeumResult = 0;
        if ($input->hasOption('with-enums') && $input->getOption('with-enums')) {
            $enumeumResult = $this->enumeumCommand->run(
                ArrayInputResolver::resolve($this->enumeumCommand->getDefinition(), $input),
                $output,
            );
        }

        $doctrineResult = $this->doctrineCommand->run(
            ArrayInputResolver::resolve($this->doctrineCommand->getDefinition(), $input),
            $output,
        );

        return max($enumeumResult, $doctrineResult);
    }
}
