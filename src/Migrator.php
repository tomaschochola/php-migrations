<?php

/**
 * @author Tomáš Chochola <tomaschochola@tomaschochola.cz>
 * @copyright © 2026 Tomáš Chochola <tomaschochola@tomaschochola.cz>
 *
 * @license CC-BY-ND-4.0
 *
 * @see {@link https://creativecommons.org/licenses/by-nd/4.0/} License
 * @see {@link https://github.com/tomaschochola} GitHub Profile
 * @see {@link https://github.com/sponsors/tomaschochola} GitHub Sponsors
 */

declare(strict_types=1);

namespace TomasChochola\Migrations;

use Override;
use Psr\Log\LoggerInterface;
use TomasChochola\Pdo\LockerInterface;
use TomasChochola\Pdo\QueryInterface;

/**
 * @no-named-arguments
 */
readonly class Migrator implements MigratorInterface
{
    private readonly LockerInterface $locker;

    private readonly LoggerInterface $logger;

    private readonly MigrationsInterface $migrations;

    private readonly QueryInterface $query;

    public function __construct(QueryInterface $query, LoggerInterface $logger, MigrationsInterface $migrations, LockerInterface $locker)
    {
        $this->query = $query;
        $this->logger = $logger;
        $this->migrations = $migrations;
        $this->locker = $locker;
    }

    /**
     * @param iterable<mixed, MigrationInterface> $migrations
     */
    #[Override]
    public function migrate(iterable $migrations): void
    {
        $this->logger->info('migrator.start');

        $lock = $this->locker->lock('migrations', 3_600);

        try {
            $this->migrations->init();

            foreach ($migrations as $migration) {
                $selector = $migration->selector();

                if ($this->migrations->has($selector)) {
                    $this->logger->info('migration.skip', ['selector' => $selector]);
                } else {
                    $this->logger->info('migration.start', ['selector' => $selector]);

                    foreach ($migration->migrate() as $sql) {
                        $this->logger->notice('migration.sql', ['selector' => $selector, 'sql' => $sql]);
                        $this->query->run($sql);
                    }

                    $this->migrations->mark($selector);
                    $this->logger->info('migration.done', ['selector' => $selector]);
                }
            }
        } finally {
            $lock->unlock();
        }

        $this->logger->info('migrator.done');
    }
}
