<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnumBundle\Command\DoctrineDecoration;

use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand as DoctrineValidateSchemaCommand;
use Enumeum\DoctrineEnum\Type\TypeRegistryLoader;
use Enumeum\DoctrineEnumBundle\Command\DoctrineDecoration\Tools\ArrayInputResolver;
use Enumeum\DoctrineEnumBundle\Command\ValidateSchemaCommand as EnumeumValidateSchemaCommand;
use Enumeum\DoctrineEnumBundle\DefinitionRegistryCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ValidateSchemaCommandDecorator extends Command
{
    /** @var string|null */
    protected static $defaultName = 'doctrine:schema:validate';

    public function __construct(
        private readonly DoctrineValidateSchemaCommand $doctrineCommand,
        private readonly EnumeumValidateSchemaCommand $enumeumCommand,
        private readonly DefinitionRegistryCollection $definitionRegistryCollection,
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
                'Run Enumeum schema validate command before the general one to validate enums schema.',
            )
            ->addOption(
                'conn',
                null,
                InputOption::VALUE_OPTIONAL,
                'The name of the connection for enumeum.',
                'default',
            )
            ->addOption(
                'ignore-unknown-enums',
                'U',
                InputOption::VALUE_NONE,
                'Do not validate database enum types which do not defined in application.',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connectionName = $input->hasOption('conn') ? $input->getOption('conn') : 'default';
        $registry = $this->definitionRegistryCollection->getRegistry($connectionName);
        TypeRegistryLoader::load($registry->getDefinitions());

        $enumeumResult = 0;
        if ($input->hasOption('with-enums') && $input->getOption('with-enums')) {
            $enumeumResult = $this->enumeumCommand->run(
                ArrayInputResolver::resolve($this->enumeumCommand->getDefinition(), $input),
                $output,
            );
        }

        $doctrineResult = $this->doctrineCommand->run(
            ArrayInputResolver::resolve($this->enumeumCommand->getDefinition(), $input),
            $output,
        );

        return max($enumeumResult, $doctrineResult);
    }
}
