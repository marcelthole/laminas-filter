<?php

declare(strict_types=1);

namespace Laminas\Filter\Compress;

use Laminas\Filter\Exception\RuntimeException;
use Laminas\Filter\File\FileInformation;

use function sprintf;

final class MimeTypeFileAdapterMatcher implements FileAdapterMatcherInterface
{
    public function match(FileInformation $file): FileCompressionAdapterInterface
    {
        $type = $file->detectMimeType();

        return match ($type) {
            'application/zip' => new ZipAdapter(),
            'application/x-tar' => new TarAdapter(),
            default => throw new RuntimeException(sprintf('Cannot handle the mime type %s', $type)),
        };
    }
}
