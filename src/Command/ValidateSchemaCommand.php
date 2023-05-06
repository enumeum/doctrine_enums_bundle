<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnumBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\Console\Command\AbstractEntityManagerCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Enumeum\DoctrineEnum\EnumTool;
use Enumeum\DoctrineEnum\Schema\SchemaManager;
use Enumeum\DoctrineEnumBundle\DefinitionRegistryCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function count;
use function sprintf;

/**
 * The DiffCommand class is responsible for generating a migration by comparing your current database schema to
 * your mapping information.
 */
final class ValidateSchemaCommand extends AbstractEntityManagerCommand
{
    /** @var string|null */
    protected static $defaultName = 'enumeum:schema:validate';

    public function __construct(
        private readonly DefinitionRegistryCollection $definitionRegistryCollection,
        ?EntityManagerProvider $entityManagerProvider = null,
    ) {
        parent::__construct($entityManagerProvider);
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Validate configured enums by comparing your current database to your enums information.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command validates enums by comparing your current database to your enums information:

    <info>%command.full_name%</info>

EOT
            )
            ->addOption(
                'em',
                null,
                InputOption::VALUE_OPTIONAL,
                'The entity manager to use for this command',
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
                'Do not sync Database types which do not defined in application.',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = (new SymfonyStyle($input, $output))->getErrorStyle();

        $ignoreUnknown = $input->hasOption('ignore-unknown-enums') && $input->getOption('ignore-unknown-enums');
        $connectionName = $input->hasOption('conn') ? $input->getOption('conn') : 'default';
        $em = $this->getEntityManager($input);
        $enumTool = $this->getEnumTool($em->getConnection(), $connectionName, $ignoreUnknown);
        $exit = 0;

        $ui->section('Database enums');

        $sqls = $enumTool->getUpdateSchemaSql();
        if (0 !== count($sqls)) {
            $ui->error('The database enums schema are not in sync with the current mapping files.');

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $ui->comment(sprintf('<info>%d</info> schema diff(s) detected:', count($sqls)));
                foreach ($sqls as $sql) {
                    $ui->text(sprintf('    %s;', $sql));
                }
            }

            $exit += 2;
        } else {
            $ui->success('The database enums schema are in sync with the mapping files.');
        }

        return $exit;
    }

    private function getEnumTool(Connection $connection, string $connectionName, bool $ignoreUnknown): EnumTool
    {
        return new EnumTool(
            SchemaManager::create(
                $this->definitionRegistryCollection->getRegistry($connectionName),
                $connection,
            ),
            $connection,
            $ignoreUnknown,
        );
    }
}
