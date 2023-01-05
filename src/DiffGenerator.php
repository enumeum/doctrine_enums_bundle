<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnumBundle;

use Doctrine\Migrations\Generator\Exception\NoChangesDetected;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Generator\SqlGenerator;
use Enumeum\DoctrineEnum\Schema\Comparator;
use Enumeum\DoctrineEnum\Schema\Schema;
use Enumeum\DoctrineEnum\Schema\SchemaManager;

class DiffGenerator
{
    public function __construct(
        private readonly Generator $migrationGenerator,
        private readonly SqlGenerator $migrationSqlGenerator,
        private readonly SchemaManager $schemaManager,
    ) {
    }

    /**
     * @throws NoChangesDetected
     */
    public function generate(
        string $fqcn,
        bool $formatted = false,
        int $lineLength = 120,
        bool $checkDbPlatform = true,
        bool $fromEmptySchema = false
    ): string {
        $fromSchema = $fromEmptySchema ? $this->createEmptySchema() : $this->createFromSchema();
        $toSchema = $this->createToSchema();

        $comparator = Comparator::create();

        $upSql = $comparator->compareSchemas($fromSchema, $toSchema)->toSql();
        $up = $this->migrationSqlGenerator->generate(
            $upSql,
            $formatted,
            $lineLength,
            $checkDbPlatform
        );

        $downSql = $comparator->compareSchemas($toSchema, $fromSchema)->toSql();
        $down = $this->migrationSqlGenerator->generate(
            $downSql,
            $formatted,
            $lineLength,
            $checkDbPlatform
        );

        if ('' === $up && '' === $down) {
            throw NoChangesDetected::new();
        }

        return $this->migrationGenerator->generateMigration(
            $fqcn,
            $up,
            $down
        );
    }

    private function createEmptySchema(): Schema
    {
        return $this->schemaManager->createSchema([]);
    }

    private function createFromSchema(): Schema
    {
        return $this->schemaManager->createSchemaFromDatabase();
    }

    private function createToSchema(): Schema
    {
        return $this->schemaManager->createSchemaFromConfig();
    }
}
