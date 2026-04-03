<?php

declare(strict_types=1);

namespace TomasChochola\Migrations;

use NoDiscard;

interface MigrationsInterface
{
    public function end(): void;

    public function execute(string $sql): void;

    #[NoDiscard]
    public function has(string $selector): bool;

    public function mark(string $selector): void;

    public function start(): void;
}
