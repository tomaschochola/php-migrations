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

use Psr\Log\LoggerInterface;
use TomasChochola\Pdo\PdoQuery;

use function assert;
use function is_callable;

/**
 * @no-named-arguments
 */
readonly class Migrator
{
    /**
     * @var callable(string): (callable(): void)
     */
    public readonly mixed $lock;

    public readonly LoggerInterface $logger;

    public readonly MigrationInterface $migration;

    public readonly PdoQuery $query;

    /**
     * @param callable(string): (callable(): void) $lock
     */
    public function __construct(PdoQuery $query, LoggerInterface $logger, MigrationInterface $migration, callable $lock)
    {
        $this->query = $query;
        $this->logger = $logger;
        $this->migration = $migration;
        $this->lock = $lock;
    }

    /**
     * @param iterable<mixed, MigrationInterface> $migrations
     */
    public function migrate(iterable $migrations): void
    {
        $unlock = ($this->lock)('migrations');

        try {
            foreach ($this->migration->migrate() as $sql) {
                $this->query->run($sql);
            }

            foreach ($migrations as $migration) {
                $selector = $migration->selector();
                $table = $this->migration->selector();
                $count = $this->query->int('SELECT COUNT(*) FROM ' . $table . ' WHERE selector = ?', [$selector]);

                $this->logger->info('migration.start', ['selector' => $selector]);

                if ($count > 0) {
                    $this->logger->info('migration.skip', ['selector' => $selector]);
                } else {
                    foreach ($migration->migrate() as $sql) {
                        $this->logger->info('migration.sql', ['selector' => $selector, 'sql' => $sql]);
                        $this->query->run($sql);
                    }

                    $this->query->run('INSERT INTO ' . $table . ' (selector) VALUES (?)', [$selector]);
                    $this->logger->info('migration.done', ['selector' => $selector]);
                }
            }
        } finally {
            $unlock();
        }
    }
}
