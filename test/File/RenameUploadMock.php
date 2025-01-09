<?php

declare(strict_types=1);

namespace LaminasTest\Filter\File;

use Laminas\Filter\File\RenameUpload;

use function rename;

/**
 * @psalm-suppress InvalidExtendClass
 */
class RenameUploadMock extends RenameUpload
{
    protected function moveUploadedFile(string $sourceFile, string $targetFile): void
    {
        rename($sourceFile, $targetFile);
    }
}
