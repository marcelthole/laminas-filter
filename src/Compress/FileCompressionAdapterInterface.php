<?php

declare(strict_types=1);

namespace Laminas\Filter\Compress;

interface FileCompressionAdapterInterface
{
    /**
     * Compress a file into the given archive
     *
     * @param non-empty-string $archivePath The full path to the target archive
     * @param non-empty-string $filePath The full path to the file to compress
     */
    public function compressFile(string $archivePath, string $filePath): void;

    /**
     * Compress an arbitrary string as the contents of a file
     *
     * @param non-empty-string $archivePath The full path to the target archive
     * @param non-empty-string $fileName The basename of the target file within the archive
     * @param string $fileContents The contents of the compressed file
     */
    public function compressStringToFile(string $archivePath, string $fileName, string $fileContents): void;

    /**
     * Compress the contents of a directory to an archive
     *
     * @param non-empty-string $archivePath The full path to the target archive
     * @param non-empty-string $directory The directory whose contents should be compressed
     */
    public function compressDirectoryContents(string $archivePath, string $directory): void;

    /**
     * Decompress a file in the given archive
     *
     * @param non-empty-string $archivePath The full path of the archive to decompress
     * @param non-empty-string $targetDirectory The directory where the archive should be expanded
     */
    public function decompressArchive(string $archivePath, string $targetDirectory): void;
}
