<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnumBundle\DependencyInjection\Compiler;

use Doctrine\Migrations\Tools\Console\Command\DiffCommand as DoctrineDiffCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand as DoctrineValidateSchemaCommand;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistryLoader;
use Enumeum\DoctrineEnum\EnumTool;
use Enumeum\DoctrineEnum\EnumUsage\TableColumnRegistry;
use Enumeum\DoctrineEnum\EventSubscriber\PostGenerateSchemaSubscriber;
use Enumeum\DoctrineEnum\Schema\SchemaManager;
use Enumeum\DoctrineEnumBundle\Command\DiffCommand;
use Enumeum\DoctrineEnumBundle\Command\DoctrineDecoration\DiffCommandDecorator;
use Enumeum\DoctrineEnumBundle\Command\DoctrineDecoration\ValidateSchemaCommandDecorator;
use Enumeum\DoctrineEnumBundle\Command\ValidateSchemaCommand;
use Enumeum\DoctrineEnumBundle\DefinitionRegistryCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use function sprintf;

/**
 * Class for Symfony bundles to register entity listeners.
 */
class ServicesPass implements CompilerPassInterface
{
    private const DEFAULT_NAME = 'default';

    public function process(ContainerBuilder $container)
    {
        $configs = $container->getParameter('doctrine_enum.config.connections');
        $isSingleConnection = 1 === count($configs);

        $definitionRegistryCollection = $container->setDefinition(
            'enumeum.definition_registry_collection',
            new Definition(DefinitionRegistryCollection::class),
        );

        $doctrineDependencyFactory = $container->getDefinition('doctrine.migrations.dependency_factory');
        $doctrineEntityManagerProvider = $container->getDefinition('doctrine.orm.command.entity_manager_provider');

        $this->compileCommands($container, $definitionRegistryCollection, $doctrineDependencyFactory, $doctrineEntityManagerProvider);
        if ($container->getParameter('doctrine_enum.config.commands_decoration')) {
            $this->compileCommandDecorators($container, $definitionRegistryCollection, $doctrineDependencyFactory);
        }

        foreach ($configs as $name => $config) {
            $connection = $container->findDefinition(sprintf('doctrine.dbal.%s_connection', $name));
            $eventManager = $container->findDefinition(sprintf('doctrine.dbal.%s_connection.event_manager', $name));

            $definitionRegistryLoader = $container->setDefinition(
                sprintf('enumeum.%s_definition_registry_loader', $name),
                new Definition(DefinitionRegistryLoader::class),
            )
                ->setFactory([DefinitionRegistryLoader::class, 'create'])
                ->setArguments([null, $config['types'], $config['paths']])
            ;
            if ($isSingleConnection || self::DEFAULT_NAME === $name) {
                $container->setAlias(DefinitionRegistryLoader::class, sprintf('enumeum.%s_definition_registry_loader', $name));
            }
            $definitionRegistryCollection->addMethodCall('addLoader', [$name, $definitionRegistryLoader]);

            $definitionRegistry = $container->setDefinition(
                sprintf('enumeum.%s_definition_registry', $name),
                new Definition(DefinitionRegistry::class),
            )
                ->setFactory([$definitionRegistryLoader, 'getRegistry'])
            ;
            if ($isSingleConnection || self::DEFAULT_NAME === $name) {
                $container->setAlias(DefinitionRegistry::class, sprintf('enumeum.%s_definition_registry', $name));
            }

            $tableColumnRegistry = $container->setDefinition(
                sprintf('enumeum.%s_table_column_registry', $name),
                new Definition(TableColumnRegistry::class, [$connection]),
            );
            if ($isSingleConnection || self::DEFAULT_NAME === $name) {
                $container->setAlias(TableColumnRegistry::class, sprintf('enumeum.%s_table_column_registry', $name));
            }

            $postGenerateSchemaSubscriber = $container->setDefinition(
                sprintf('enumeum.%s_post_generate_schema_subscriber', $name),
                new Definition(PostGenerateSchemaSubscriber::class, [$definitionRegistry, $tableColumnRegistry]),
            );
            $eventManager->addMethodCall('addEventSubscriber', [$postGenerateSchemaSubscriber]);

            $schemaManager = $container->setDefinition(
                sprintf('enumeum.%s_schema_manager', $name),
                new Definition(SchemaManager::class),
            )
                ->setFactory([SchemaManager::class, 'create'])
                ->setArguments([$definitionRegistry, $connection])
            ;
            if ($isSingleConnection || self::DEFAULT_NAME === $name) {
                $container->setAlias(SchemaManager::class, sprintf('enumeum.%s_schema_manager', $name));
            }

            $container->setDefinition(
                sprintf('enumeum.%s_enum_tool', $name),
                new Definition(EnumTool::class, [$schemaManager, $connection]),
            );
            if ($isSingleConnection || self::DEFAULT_NAME === $name) {
                $container->setAlias(EnumTool::class, sprintf('enumeum.%s_enum_tool', $name));
            }
        }
    }

    private function compileCommands(
        ContainerBuilder $container,
        Definition $definitionRegistryCollection,
        Definition $doctrineDependencyFactory,
        Definition $doctrineEntityManagerProvider,
    ): void {
        $enumeumDiffCommand = $container->setDefinition(
            'enumeum.migrations.diff_command',
            new Definition(DiffCommand::class, [
                $definitionRegistryCollection,
                $doctrineDependencyFactory,
            ]),
        );
        $enumeumDiffCommand->addTag('console.command', ['command' => 'enumeum:migrations:diff']);

        $enumeumSchemaValidateCommand = $container->setDefinition(
            'enumeum.schema.validate_command',
            new Definition(ValidateSchemaCommand::class, [
                $definitionRegistryCollection,
                $doctrineEntityManagerProvider,
            ]),
        );
        $enumeumSchemaValidateCommand->addTag('console.command', ['command' => 'enumeum:schema:validate']);
    }

    private function compileCommandDecorators(
        ContainerBuilder $container,
        Definition $definitionRegistryCollection,
        Definition $doctrineDependencyFactory,
    ): void {
        $doctrineDiffCommandDecorator = $container->setDefinition(
            'enumeum.doctrine_diff_command_decorator',
            new Definition(DiffCommandDecorator::class, [
                    new Reference('enumeum.doctrine_diff_command_decorator.inner'),
                    new Reference('enumeum.migrations.diff_command'),
                    $definitionRegistryCollection,
                    $doctrineDependencyFactory,
                ],
            ),
        );
        $doctrineDiffCommandDecorator->setDecoratedService('doctrine_migrations.diff_command');
        $container->setAlias(DoctrineDiffCommand::class, 'enumeum.doctrine_diff_command_decorator.inner');

        $doctrineValidateSchemaCommandDecorator = $container->setDefinition(
            'enumeum.doctrine_validate_schema_command_decorator',
            new Definition(ValidateSchemaCommandDecorator::class, [
                    new Reference('enumeum.doctrine_validate_schema_command_decorator.inner'),
                    new Reference('enumeum.schema.validate_command'),
                    $definitionRegistryCollection,
                ],
            ),
        );
        $doctrineValidateSchemaCommandDecorator->setDecoratedService('doctrine.schema_validate_command');
        $container->setAlias(DoctrineValidateSchemaCommand::class, 'enumeum.doctrine_validate_schema_command_decorator.inner');
    }
}
