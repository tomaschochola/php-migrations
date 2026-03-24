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
readonly class PdoMigrator
{
    /**
     * @var callable(string): mixed
     */
    public readonly mixed $lock;

    public readonly LoggerInterface $logger;

    public readonly MigrationInterface $migration;

    public readonly PdoQuery $query;

    /**
     * @param callable(string): mixed $lock
     */
    public function __construct(PdoQuery $query, LoggerInterface $logger, MigrationInterface $migration, mixed $lock)
    {
        $this->query = $query;
        $this->logger = $logger;
        $this->migration = $migration;
        $this->lock = $lock;
    }

    /**
     * @param iterable<mixed, MigrationInterface> $migrations
     */
    public function forward(iterable $migrations): void
    {
        assert(is_callable($this->lock));

        $unlock = ($this->lock)('migrations');

        assert(is_callable($unlock));

        try {
            $this->init();

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

    private function init(): void
    {
        foreach ($this->migration->migrate() as $sql) {
            $this->query->run($sql);
        }
    }
}
