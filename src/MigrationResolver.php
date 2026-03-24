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

use Iterator;
use NoDiscard;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @no-named-arguments
 */
readonly class MigrationResolver
{
    public readonly ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[NoDiscard]
    public static function inject(ContainerInterface $container): self
    {
        return new self($container);
    }

    /**
     * @param iterable<mixed, class-string<MigrationInterface>> $migrations
     *
     * @return Iterator<mixed, MigrationInterface>
     */
    #[NoDiscard]
    public function resolve(iterable $migrations): Iterator
    {
        foreach ($migrations as $class) {
            $migration = $this->container->get($class);

            assert($migration instanceof MigrationInterface);

            yield $migration;
        }
    }
}
