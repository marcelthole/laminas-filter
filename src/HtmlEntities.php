<?php

declare(strict_types=1);

namespace Laminas\Filter;

use function function_exists;
use function htmlentities;
use function iconv;
use function is_scalar;
use function strlen;

use const ENT_QUOTES;

/**
 * @psalm-type Options = array{
 *     quotestyle?: int,
 *     encoding?: string,
 *     doublequote?: bool,
 * }
 * @implements FilterInterface<string>
 */
final class HtmlEntities implements FilterInterface
{
    /**
     * Corresponds to the second htmlentities() argument
     */
    private readonly int $quoteStyle;

    /**
     * Corresponds to the third htmlentities() argument
     */
    private readonly string $encoding;

    /**
     * Corresponds to the forth htmlentities() argument
     */
    private readonly bool $doubleQuote;

    /**
     * Sets filter options
     *
     * @param Options $options
     */
    public function __construct(array $options = [])
    {
        $this->quoteStyle  = $options['quotestyle'] ?? ENT_QUOTES;
        $this->encoding    = $options['encoding'] ?? 'UTF-8';
        $this->doubleQuote = $options['doublequote'] ?? true;
    }

    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * Returns the string $value, converting characters to their corresponding HTML entity
     * equivalents where they exist
     *
     * If the value provided is non-scalar, the value will remain unfiltered
     *
     * @throws Exception\DomainException On encoding mismatches.
     * @psalm-return ($value is scalar ? string : mixed)
     */
    public function filter(mixed $value): mixed
    {
        if (! is_scalar($value)) {
            return $value;
        }
        $value = (string) $value;

        $filtered = htmlentities($value, $this->quoteStyle, $this->encoding, $this->doubleQuote);
        if (strlen($value) && ! strlen($filtered)) {
            if (! function_exists('iconv')) {
                throw new Exception\DomainException('Encoding mismatch has resulted in htmlentities errors');
            }
            $value    = iconv('', $this->encoding . '//IGNORE', $value);
            $filtered = htmlentities($value, $this->quoteStyle, $this->encoding, $this->doubleQuote);
            if (! strlen($filtered)) {
                throw new Exception\DomainException('Encoding mismatch has resulted in htmlentities errors');
            }
        }
        return $filtered;
    }

    public function __invoke(mixed $value): mixed
    {
        return $this->filter($value);
    }
}
