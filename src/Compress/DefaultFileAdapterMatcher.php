<?php

declare(strict_types=1);

namespace Laminas\Filter\Compress;

use Laminas\Filter\Exception\RuntimeException;
use Laminas\Filter\File\FileInformation;

use function basename;
use function pathinfo;
use function sprintf;
use function str_ends_with;
use function strtolower;

use const PATHINFO_EXTENSION;

final class DefaultFileAdapterMatcher implements FileAdapterMatcherInterface
{
    public function match(FileInformation $file): FileCompressionAdapterInterface
    {
        $file      = strtolower(basename($file->path));
        $ext       = pathinfo($file, PATHINFO_EXTENSION);
        $ext       = $ext === '' ? $file : $ext;
        $extension = str_ends_with($file, 'tar.gz') ? 'tar' : $ext;
        $extension = str_ends_with($file, 'tar.bz2') ? 'tar' : $extension;

        return match ($extension) {
            'zip' => new ZipAdapter(),
            'tar' => new TarAdapter(),
            default => throw new RuntimeException(sprintf('Cannot handle the filename extension %s', $extension)),
        };
    }
}
