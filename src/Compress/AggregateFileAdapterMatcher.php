<?php

declare(strict_types=1);

namespace Laminas\Filter\Compress;

use Laminas\Filter\Exception\RuntimeException;
use Laminas\Filter\File\FileInformation;

use function array_values;
use function sprintf;

final class AggregateFileAdapterMatcher implements FileAdapterMatcherInterface
{
    /** @var list<FileAdapterMatcherInterface> */
    private readonly array $matchers;

    public function __construct(FileAdapterMatcherInterface ...$matchers)
    {
        $this->matchers = array_values($matchers);
    }

    public function match(FileInformation $file): FileCompressionAdapterInterface
    {
        foreach ($this->matchers as $matcher) {
            try {
                return $matcher->match($file);
            } catch (RuntimeException) {
            }
        }

        throw new RuntimeException(sprintf('No matchers were able to resolve the file %s', $file->path));
    }
}
