<?php

declare(strict_types=1);

namespace Laminas\Filter\Compress;

use Laminas\Filter\Exception\RuntimeException;

use function basename;
use function pathinfo;
use function sprintf;
use function str_ends_with;
use function strtolower;

use const PATHINFO_EXTENSION;

final class DefaultFileAdapterMatcher implements FileAdapterMatcherInterface
{
    public function matchFilenameExtension(string $path): FileCompressionAdapterInterface
    {
        $path      = strtolower(basename($path));
        $ext       = pathinfo($path, PATHINFO_EXTENSION);
        $ext       = $ext === '' ? $path : $ext;
        $extension = str_ends_with($path, 'tar.gz') ? 'tar' : $ext;

        return match ($extension) {
            'zip' => new ZipAdapter(),
            'tar' => new TarAdapter(),
            default => throw new RuntimeException(sprintf('Cannot handle the filename extension %s', $extension)),
        };
    }
}
