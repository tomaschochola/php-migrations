<?php

declare(strict_types=1);

namespace TomasChochola\Migrations;

use Override;
use Psr\Log\LoggerInterface;

readonly class Migrator implements MigratorInterface
{
    private readonly LoggerInterface $logger;
    private readonly MigrationsInterface $migrations;

    public function __construct(LoggerInterface $logger, MigrationsInterface $migrations)
    {
        $this->logger = $logger;
        $this->migrations = $migrations;
    }

    /**
     * @param iterable<mixed, MigrationInterface> $migrations
     */
    #[Override]
    public function migrate(iterable $migrations): void
    {
        $this->logger->info('migrator.start');
        $this->migrations->start();

        foreach ($migrations as $migration) {
            $selector = $migration->selector();

            if ($this->migrations->has($selector)) {
                $this->logger->info('migration.skip', ['selector' => $selector]);
            } else {
                $this->logger->info('migration.start', ['selector' => $selector]);

                foreach ($migration->migrate() as $sql) {
                    $this->logger->notice('migration.sql', ['selector' => $selector, 'sql' => $sql]);
                    $this->migrations->execute($sql);
                }

                $this->migrations->mark($selector);
                $this->logger->info('migration.done', ['selector' => $selector]);
            }
        }

        $this->migrations->end();
        $this->logger->info('migrator.done');
    }
}
