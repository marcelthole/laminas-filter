<?php

declare(strict_types=1);

namespace Laminas\Filter;

use function dirname;
use function is_scalar;

/**
 * @psalm-type Options = array{}
 * @implements FilterInterface<string>
 */
final class Dir implements FilterInterface
{
    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * Returns dirname($value)
     *
     * @psalm-return string
     */
    public function filter(mixed $value): mixed
    {
        if (! is_scalar($value)) {
            return $value;
        }
        $value = (string) $value;

        return dirname($value);
    }

    public function __invoke(mixed $value): mixed
    {
        return $this->filter($value);
    }
}
