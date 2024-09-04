<?php

declare(strict_types=1);

namespace Laminas\Filter;

use Closure;
use Stringable;

use function array_map;
use function is_array;
use function is_scalar;

/**
 * @internal
 *
 * @psalm-internal \Laminas
 * @psalm-internal \LaminasTest
 */
final class ScalarOrArrayFilterCallback
{
    /**
     * Recursively applies a callback to an array of scalars or scalar input. Non-scalar values are skipped.
     *
     * @template T
     * @param T $value
     * @param Closure(string): string $callback
     * @return T|string|array<array-key, string|mixed>
     */
    public static function apply(mixed $value, Closure $callback): mixed
    {
        if (is_scalar($value) || $value instanceof Stringable) {
            return $callback((string) $value);
        }

        if (is_array($value)) {
            return array_map(
                static fn (mixed $value): mixed => self::apply($value, $callback),
                $value,
            );
        }

        return $value;
    }
}
