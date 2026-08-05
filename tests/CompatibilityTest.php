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

namespace Tests;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\AbstractLogger;
use Stringable;
use TomasChochola\Migrations\MigrationInterface;
use TomasChochola\Migrations\MigrationsInterface;
use TomasChochola\Migrations\Migrator;

use function in_array;

/**
 * @internal
 *
 * @no-named-arguments
 */
#[CoversClass(Migrator::class)]
#[Small()]
final class CompatibilityTest extends TestCase
{
    #[Test()]
    public function appliedMigrationsAreSkippedAndPendingMigrationsAreCompleted(): void
    {
        $logger = new RecordingLogger();
        $migrations = new RecordingMigrations(['already-applied']);
        $applied = new TestMigration('already-applied', ['SELECT skipped']);
        $pending = new TestMigration('pending', ['SELECT first', 'SELECT second']);

        (new Migrator($logger, $migrations))->migrate([$applied, $pending]);

        self::assertSame(0, $applied->migrationCalls);
        self::assertSame(1, $pending->migrationCalls);
        self::assertSame(['already-applied', 'pending'], $migrations->applied);

        self::assertSame([
            'start',
            'has:already-applied',
            'has:pending',
            'execute:SELECT first',
            'execute:SELECT second',
            'mark:pending',
            'end',
        ], $migrations->events);

        self::assertSame([
            ['level' => 'info', 'message' => 'migrator.start', 'context' => []],
            ['level' => 'info', 'message' => 'migration.skip', 'context' => ['selector' => 'already-applied']],
            ['level' => 'info', 'message' => 'migration.start', 'context' => ['selector' => 'pending']],
            ['level' => 'notice', 'message' => 'migration.sql', 'context' => ['selector' => 'pending', 'sql' => 'SELECT first']],
            ['level' => 'notice', 'message' => 'migration.sql', 'context' => ['selector' => 'pending', 'sql' => 'SELECT second']],
            ['level' => 'info', 'message' => 'migration.done', 'context' => ['selector' => 'pending']],
            ['level' => 'info', 'message' => 'migrator.done', 'context' => []],
        ], $logger->records);
    }
}

/**
 * @no-named-arguments
 */
final class RecordingLogger extends AbstractLogger
{
    /**
     * @var list<array{level: mixed, message: string, context: array<mixed, mixed>}>
     */
    public array $records = [];

    #[Override()]
    public function log(mixed $level, Stringable | string $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
    }
}

/**
 * @no-named-arguments
 */
final class RecordingMigrations implements MigrationsInterface
{
    /**
     * @var list<string>
     */
    public array $applied;

    /**
     * @var list<string>
     */
    public array $events = [];

    /**
     * @param list<string> $applied
     */
    public function __construct(array $applied)
    {
        $this->applied = $applied;
    }

    #[Override()]
    public function end(): void
    {
        $this->events[] = 'end';
    }

    #[Override()]
    public function execute(Stringable | string $sql): void
    {
        $this->events[] = 'execute:' . $sql;
    }

    #[Override()]
    public function has(string $selector): bool
    {
        $this->events[] = 'has:' . $selector;

        return in_array($selector, $this->applied, true);
    }

    #[Override()]
    public function mark(string $selector): void
    {
        $this->events[] = 'mark:' . $selector;
        $this->applied[] = $selector;
    }

    #[Override()]
    public function start(): void
    {
        $this->events[] = 'start';
    }
}

/**
 * @no-named-arguments
 */
final class TestMigration implements MigrationInterface
{
    public int $migrationCalls = 0;

    private string $selector;

    /**
     * @var list<string>
     */
    private array $statements;

    /**
     * @param list<string> $statements
     */
    public function __construct(string $selector, array $statements)
    {
        $this->selector = $selector;
        $this->statements = $statements;
    }

    #[Override()]
    public function migrate(): iterable
    {
        ++$this->migrationCalls;

        return $this->statements;
    }

    #[Override()]
    public function selector(): string
    {
        return $this->selector;
    }
}
