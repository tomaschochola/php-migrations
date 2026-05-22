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
