<?php

declare(strict_types=1);

namespace Laminas\Filter\File;

use Laminas\Filter\Exception;
use Laminas\Filter\FilterInterface;
use Laminas\Stdlib\ErrorHandler;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

use function basename;
use function file_exists;
use function filesize;
use function is_array;
use function is_dir;
use function is_string;
use function move_uploaded_file;
use function pathinfo;
use function spl_object_hash;
use function sprintf;
use function str_replace;
use function strlen;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const UPLOAD_ERR_OK;

/**
 * @psalm-type Options = array{
 *     target?: string|null,
 *     use_upload_name?: bool,
 *     use_upload_extension?: bool,
 *     overwrite?: bool,
 *     randomize?: bool,
 *     stream_factory?: StreamFactoryInterface|null,
 *     upload_file_factory?: UploadedFileFactoryInterface|null,
 * }
 * @template TOptions of Options
 * @implements FilterInterface<string|array|UploadedFileInterface>
 */
class RenameUpload implements FilterInterface
{
    private readonly string|null $target;
    private readonly bool $useUploadName;
    private readonly bool $useUploadExtension;
    private readonly bool $overwrite;
    private readonly bool $randomize;
    private readonly StreamFactoryInterface|null $streamFactory;
    private readonly UploadedFileFactoryInterface|null $uploadFileFactory;

    /**
     * Store already filtered values, so we can filter multiple
     * times the same file without being block by move_uploaded_file
     * internal checks
     */
    private array $alreadyFiltered = [];

    /**
     * @param Options $options The target file path or an options array
     */
    public function __construct(array $options = [])
    {
        $this->target             = $options['target'] ?? null;
        $this->useUploadName      = $options['use_upload_name'] ?? false;
        $this->useUploadExtension = $options['use_upload_extension'] ?? false;
        $this->overwrite          = $options['overwrite'] ?? false;
        $this->randomize          = $options['randomize'] ?? false;
        $this->streamFactory      = $options['stream_factory'] ?? null;
        $this->uploadFileFactory  = $options['upload_file_factory'] ?? null;
    }

    /**
     * Defined by Laminas\Filter\Filter
     *
     * Renames the file $value to the new name set before
     * Returns the file $value, removing all but digit characters
     *
     * @param  string|array|UploadedFileInterface $value Full path of file to
     *     change; $_FILES data array; or UploadedFileInterface instance.
     * @return string|array|UploadedFileInterface Returns one of the following:
     *     - New filename, for string $value
     *     - Array with tmp_name and name keys for array $value
     *     - UploadedFileInterface for UploadedFileInterface $value
     * @throws Exception\RuntimeException
     */
    public function filter(mixed $value): mixed
    {
        // PSR-7 uploaded file
        if ($value instanceof UploadedFileInterface) {
            return $this->filterPsr7UploadedFile($value);
        }

        // File upload via traditional SAPI
        if (is_array($value) && isset($value['tmp_name'])) {
            return $this->filterSapiUploadedFile($value);
        }

        // String filename
        if (is_string($value)) {
            return $this->filterStringFilename($value);
        }

        // Unrecognized; return verbatim
        return $value;
    }

    public function __invoke(mixed $value): mixed
    {
        return $this->filter($value);
    }

    /**
     * @throws Exception\RuntimeException
     */
    protected function moveUploadedFile(string $sourceFile, string $targetFile): bool
    {
        ErrorHandler::start();
        $result           = move_uploaded_file($sourceFile, $targetFile);
        $warningException = ErrorHandler::stop();
        if (! $result || null !== $warningException) {
            throw new Exception\RuntimeException(
                sprintf("File '%s' could not be renamed. An error occurred while processing the file.", $sourceFile),
                0,
                $warningException
            );
        }

        return $result;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    protected function deleteIfFileExists(string $targetFile): void
    {
        if (! file_exists($targetFile)) {
            return;
        }

        if (! $this->overwrite) {
            throw new Exception\InvalidArgumentException(
                sprintf("File '%s' could not be renamed. It already exists.", $targetFile)
            );
        }

        unlink($targetFile);
    }

    protected function getFinalTarget(string $source, string|null $clientFileName): string
    {
        $target = $this->target;
        if ($target === null || $target === '*') {
            $target = $source;
        }

        // Get the target directory
        if (is_dir($target)) {
            $targetDir = $target;
            $last      = $target[strlen($target) - 1];
            if (($last !== '/') && ($last !== '\\')) {
                $targetDir .= DIRECTORY_SEPARATOR;
            }
        } else {
            $info      = pathinfo($target);
            $targetDir = $info['dirname'] . DIRECTORY_SEPARATOR;
        }

        // Get the target filename
        if ($this->useUploadName) {
            $targetFile = basename($clientFileName);
        } elseif (! is_dir($target)) {
            $targetFile = basename($target);
            if ($this->useUploadExtension && ! $this->randomize) {
                $targetInfo = pathinfo($targetFile);
                $sourceinfo = pathinfo($clientFileName);
                if (isset($sourceinfo['extension'])) {
                    $targetFile = $targetInfo['filename'] . '.' . $sourceinfo['extension'];
                }
            }
        } else {
            $targetFile = basename($source);
        }

        if ($this->randomize) {
            $targetFile = $this->applyRandomToFilename($clientFileName, $targetFile);
        }

        return $targetDir . $targetFile;
    }

    protected function applyRandomToFilename(string $source, string $filename): string
    {
        $info     = pathinfo($filename);
        $filename = $info['filename'] . str_replace('.', '_', uniqid('_', true));

        $sourceinfo = pathinfo($source);

        $extension = '';
        if ($this->useUploadExtension === true && isset($sourceinfo['extension'])) {
            $extension .= '.' . $sourceinfo['extension'];
        } elseif (isset($info['extension'])) {
            $extension .= '.' . $info['extension'];
        }

        return $filename . $extension;
    }

    private function filterStringFilename(string $fileName): string
    {
        if (isset($this->alreadyFiltered[$fileName])) {
            return $this->alreadyFiltered[$fileName];
        }

        $targetFile = $this->getFinalTarget($fileName, $fileName);
        if ($fileName === $targetFile || ! file_exists($fileName)) {
            return $fileName;
        }

        $this->deleteIfFileExists($targetFile);
        $this->moveUploadedFile($fileName, $targetFile);
        $this->alreadyFiltered[$fileName] = $targetFile;

        return $this->alreadyFiltered[$fileName];
    }

    /**
     * @param  array<string, mixed> $fileData
     * @return array<string, string>
     */
    private function filterSapiUploadedFile(array $fileData): array
    {
        $sourceFile = $fileData['tmp_name'];

        if (isset($this->alreadyFiltered[$sourceFile])) {
            return $this->alreadyFiltered[$sourceFile];
        }

        $clientFilename = $fileData['name'];

        $targetFile = $this->getFinalTarget($sourceFile, $clientFilename);
        if ($sourceFile === $targetFile || ! file_exists($sourceFile)) {
            return $fileData;
        }

        $this->deleteIfFileExists($targetFile);
        $this->moveUploadedFile($sourceFile, $targetFile);

        $this->alreadyFiltered[$sourceFile]             = $fileData;
        $this->alreadyFiltered[$sourceFile]['tmp_name'] = $targetFile;

        return $this->alreadyFiltered[$sourceFile];
    }

    /**
     * @throws Exception\RuntimeException If no stream factory is composed in the filter.
     * @throws Exception\RuntimeException If no uploaded file factory is composed in the filter.
     */
    private function filterPsr7UploadedFile(UploadedFileInterface $uploadedFile): UploadedFileInterface
    {
        $alreadyFilteredKey = spl_object_hash($uploadedFile);

        if (isset($this->alreadyFiltered[$alreadyFilteredKey])) {
            return $this->alreadyFiltered[$alreadyFilteredKey];
        }

        $sourceFile     = $uploadedFile->getStream()->getMetadata('uri');
        $clientFilename = $uploadedFile->getClientFilename();
        $targetFile     = $this->getFinalTarget($sourceFile, $clientFilename);

        if ($sourceFile === $targetFile || ! file_exists($sourceFile)) {
            return $uploadedFile;
        }

        $this->deleteIfFileExists($targetFile);
        $uploadedFile->moveTo($targetFile);

        $streamFactory = $this->streamFactory;
        if (! $streamFactory) {
            throw new Exception\RuntimeException(sprintf(
                'No PSR-17 %s present; cannot filter file. Please pass the stream_factory'
                . ' option with a %s instance when creating the filter for use with PSR-7.',
                StreamFactoryInterface::class,
                StreamFactoryInterface::class
            ));
        }

        $stream = $streamFactory->createStreamFromFile($targetFile);

        $uploadedFileFactory = $this->uploadFileFactory;
        if (! $uploadedFileFactory) {
            throw new Exception\RuntimeException(sprintf(
                'No PSR-17 %s present; cannot filter file. Please pass the upload_file_factory'
                . ' option with a %s instance when creating the filter for use with PSR-7.',
                UploadedFileFactoryInterface::class,
                UploadedFileFactoryInterface::class
            ));
        }

        $this->alreadyFiltered[$alreadyFilteredKey] = $uploadedFileFactory->createUploadedFile(
            $stream,
            filesize($targetFile),
            UPLOAD_ERR_OK,
            $uploadedFile->getClientFilename(),
            $uploadedFile->getClientMediaType()
        );

        return $this->alreadyFiltered[$alreadyFilteredKey];
    }
}
