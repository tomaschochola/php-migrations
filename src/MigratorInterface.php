<?php

declare(strict_types=1);

namespace TomasChochola\Migrations;

/**
 * @no-named-arguments
 */
interface MigratorInterface
{
    /**
     * @param iterable<mixed, MigrationInterface> $migrations
     */
    public function migrate(iterable $migrations): void;
}
