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
interface MigrationsInterface
{
    public function end(): void;

    public function execute(Stringable|string $sql): void;

    #[NoDiscard]
    public function has(string $selector): bool;

    public function mark(string $selector): void;

    public function start(): void;
}
