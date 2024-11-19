<?php

declare(strict_types=1);

namespace Laminas\Filter;

use Laminas\Filter\Compress\AggregateFileAdapterMatcher;
use Laminas\Filter\Compress\FileAdapterMatcherInterface;
use Laminas\Filter\Compress\FileExtensionAdapterMatcher;
use Laminas\Filter\Compress\MimeTypeFileAdapterMatcher;
use Laminas\Filter\Exception\InvalidArgumentException;
use Laminas\Filter\Exception\RuntimeException;
use Laminas\Filter\File\FileInformation;

use function is_dir;
use function is_writable;

/**
 * @psalm-type Options = array{
 *     target: non-empty-string,
 *     matcher?: FileAdapterMatcherInterface,
 * }
 * @implements FilterInterface<non-empty-string>
 */
final class DecompressArchive implements FilterInterface
{
    /** @var non-empty-string */
    private readonly string $target;
    private FileAdapterMatcherInterface $matcher;

    /** @param Options $options */
    public function __construct(array $options)
    {
        $target = $options['target'];
        if (! is_dir($target) || ! is_writable($target)) {
            throw new InvalidArgumentException(
                'The target directory %s is either not a directory, or it cannot be written to',
            );
        }

        $this->target  = $target;
        $this->matcher = $options['matcher'] ?? new AggregateFileAdapterMatcher(
            new MimeTypeFileAdapterMatcher(),
            new FileExtensionAdapterMatcher(),
        );
    }

    public function filter(mixed $value): mixed
    {
        if (! FileInformation::isPossibleFile($value)) {
            return $value;
        }

        $file = FileInformation::factory($value);
        try {
            $adapter = $this->matcher->match($file);
        } catch (RuntimeException) {
            return $value;
        }

        $adapter->decompressArchive($file->path, $this->target);

        return $this->target;
    }

    public function __invoke(mixed $value): mixed
    {
        return $this->filter($value);
    }
}
