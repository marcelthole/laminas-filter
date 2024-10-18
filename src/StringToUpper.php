<?php

declare(strict_types=1);

namespace Laminas\Filter;

use function is_scalar;
use function mb_strtoupper;

/**
 * @psalm-type Options = array{encoding?: string}
 * @implements FilterInterface<string>
 */
final class StringToUpper implements FilterInterface
{
    private readonly string $encoding;

    /**
     * @param Options $options
     */
    public function __construct(array $options = [])
    {
        $this->encoding = EncodingOption::assertWithDefault($options['encoding'] ?? null);
    }

    /**
     * Returns the string $value, converting characters to uppercase as necessary
     *
     * If the value provided is non-scalar, the value will remain unfiltered
     */
    public function filter(mixed $value): mixed
    {
        if (! is_scalar($value)) {
            return $value;
        }

        return mb_strtoupper((string) $value, $this->encoding);
    }

    public function __invoke(mixed $value): mixed
    {
        return $this->filter($value);
    }
}
