<?php

declare(strict_types=1);

namespace Laminas\Filter\Compress;

use Laminas\Filter\Exception\RuntimeException;

interface FileAdapterMatcherInterface
{
    /**
     * Return an adapter instance based on the filename extension of the given file path
     *
     * Implementations should also accept just the extension, i.e. 'zip' in a case-insensitive manner
     *
     * @param non-empty-string $path
     * @throws RuntimeException If the matcher cannot figure out which adapter to use.
     */
    public function matchFilenameExtension(string $path): FileCompressionAdapterInterface;
}
