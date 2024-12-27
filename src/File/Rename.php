<?php

declare(strict_types=1);

namespace Laminas\Filter\File;

use Laminas\Filter\Exception;
use Laminas\Filter\FilterInterface;

use function array_key_exists;
use function basename;
use function count;
use function file_exists;
use function is_array;
use function is_bool;
use function is_dir;
use function is_scalar;
use function is_string;
use function pathinfo;
use function realpath;
use function rename;
use function sprintf;
use function strlen;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * @psalm-type Options = array{
 *     target: string,
 *     source?: string,
 *     overwrite?: bool,
 *     randomize?: bool,
 * }
 * @template TOptions of Options
 * @implements FilterInterface<mixed>
 */
final class Rename implements FilterInterface
{
    /**
     * Internal array of array(source, target, overwrite)
     *
     * @var list<array{source: string, target: string, overwrite: bool, randomize: bool}>
     */
    private array $files = [];

    /**
     * @param  Options|list<Options> $options
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $options = [])
    {
        $this->convertOptions($options);
    }

    /**
     * Defined by Laminas\Filter\Filter
     *
     * Renames the file $value to the new name set before
     * Returns the file $value, removing all but digit characters
     *
     * @param mixed $value Full path of file to change or $_FILES data array
     * @return mixed|string|array The new filename which has been set
     * @throws Exception\RuntimeException
     */
    public function filter(mixed $value): mixed
    {
        if (! is_scalar($value) && ! is_array($value)) {
            return $value;
        }

        // An uploaded file? Retrieve the 'tmp_name'
        $isFileUpload = false;
        if (is_array($value)) {
            if (! isset($value['tmp_name'])) {
                return $value;
            }

            $isFileUpload = true;
            $uploadData   = $value;
            $value        = $value['tmp_name'];
        }

        $file = $this->getNewName((string) $value);
        if (is_string($file)) {
            if ($isFileUpload) {
                return $uploadData;
            } else {
                return $file;
            }
        }

        $result = rename($file['source'], $file['target']);

        if ($result !== true) {
            throw new Exception\RuntimeException(
                sprintf(
                    "File '%s' could not be renamed. "
                    . "An error occurred while processing the file.",
                    $value
                )
            );
        }

        if ($isFileUpload) {
            $uploadData['tmp_name'] = $file['target'];
            return $uploadData;
        }
        return $file['target'];
    }

    public function __invoke(mixed $value): mixed
    {
        return $this->filter($value);
    }

    /**
     * Internal method for creating the file array
     * Supports single and nested arrays
     */
    private function convertOptions(array $options): void
    {
        $files = [];
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $this->convertOptions($value);
                continue;
            }

            switch ($key) {
                case 'source':
                    $files['source'] = (string) $value;
                    break;

                case 'target':
                    $files['target'] = (string) $value;
                    break;

                case 'overwrite':
                    $files['overwrite'] = (bool) $value;
                    break;

                case 'randomize':
                    $files['randomize'] = (bool) $value;
                    break;

                default:
                    break;
            }
        }

        if ($files === []) {
            return;
        }

        if (! is_string($files['source'] ?? null)) {
            $files['source'] = '*';
        }

        if (! is_string($files['target'] ?? null)) {
            $files['target'] = '*';
        }

        if (! is_bool($files['overwrite'] ?? null)) {
            $files['overwrite'] = false;
        }

        if (! is_bool($files['randomize'] ?? null)) {
            $files['randomize'] = false;
        }

        $found = false;
        foreach ($this->files as $key => $value) {
            if ($value['source'] === $files['source']) {
                $this->files[$key] = $files;
                $found             = true;
            }
        }

        if (! $found) {
            $count               = count($this->files);
            $this->files[$count] = $files;
        }
    }

    /**
     * Internal method to resolve the requested source
     * and return all other related parameters
     */
    private function getFileName(string $file): array
    {
        $rename = [];
        foreach ($this->files as $value) {
            if ($value['source'] === '*') {
                if (! isset($rename['source'])) {
                    $rename           = $value;
                    $rename['source'] = $file;
                }
            }

            if ($value['source'] === $file) {
                $rename = $value;
                break;
            }
        }

        if (! isset($rename['source'])) {
            return [];
        }

        if (! isset($rename['target']) || $rename['target'] === '*') {
            $rename['target'] = $rename['source'];
        }

        if (is_dir($rename['target'])) {
            $name = basename($rename['source']);
            $last = $rename['target'][strlen($rename['target']) - 1];
            if ($last !== '/' && $last !== '\\') {
                $rename['target'] .= DIRECTORY_SEPARATOR;
            }

            $rename['target'] .= $name;
        }

        if ($rename['randomize']) {
            $info      = pathinfo($rename['target']);
            $newTarget = $info['dirname'] . DIRECTORY_SEPARATOR
                . $info['filename'] . uniqid('_', false);
            if (isset($info['extension'])) {
                $newTarget .= '.' . $info['extension'];
            }
            $rename['target'] = $newTarget;
        }

        return $rename;
    }

    /**
     * Returns only the new filename without moving it
     * But existing files will be erased when the overwrite option is true
     *
     * @throws Exception\InvalidArgumentException If the target file already exists.
     */
    private function getNewName(string $value): string|array
    {
        $file = $this->getFileName($value);

        if ($file === []) {
            return $value;
        }

        if ($file['source'] === $file['target']) {
            return $value;
        }

        if (! array_key_exists('source', $file) || ! file_exists($file['source'])) {
            return $value;
        }

        if ($file['overwrite'] && file_exists($file['target'])) {
            unlink($file['target']);
        }

        if (file_exists($file['target'])) {
            throw new Exception\InvalidArgumentException(sprintf(
                '"File "%s" could not be renamed to "%s"; target file already exists',
                $value,
                realpath($file['target'])
            ));
        }

        return $file;
    }
}
