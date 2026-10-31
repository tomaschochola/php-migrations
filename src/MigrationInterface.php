<?php

declare(strict_types=1);

namespace TomasChochola\Migrations;

use NoDiscard;
use Stringable;

/**
 * @no-named-arguments
 */
interface MigrationInterface
{
    /**
     * @return iterable<mixed, Stringable|string>
     */
    #[NoDiscard()]
    public function migrate(): iterable;

    #[NoDiscard()]
    public function selector(): string;
}
