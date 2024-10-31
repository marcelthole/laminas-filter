<?php

declare(strict_types=1);

namespace Laminas\Filter\Word;

use Laminas\Filter\AbstractFilter;

use function is_array;

/**
 * @psalm-type Options = array{
 *     separator?: string,
 *     ...
 * }
 * @template TOptions of Options
 * @extends AbstractFilter<TOptions>
 */
abstract class AbstractSeparator extends AbstractFilter
{
    protected string $separator = ' ';

    /**
     * @param Options|string $separator Space by default
     */
    public function __construct(string|array $separator = ' ')
    {
        if (is_array($separator) && isset($separator['separator'])) {
            $this->setSeparator($separator['separator']);

            return;
        }

        $this->setSeparator($separator);
    }

    /**
     * @deprecated Since 2.39.0 All option setters and getters will be removed in version 3.0
     *
     * @return $this
     */
    public function setSeparator(string $separator): self
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * @deprecated Since 2.39.0 All option setters and getters will be removed in version 3.0
     */
    public function getSeparator(): string
    {
        return $this->separator;
    }
}
