<?php

declare(strict_types=1);

namespace LaminasTest\Filter\File;

use Laminas\Filter\File\Rename as FileRename;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function copy;
use function dirname;
use function file_exists;
use function is_dir;
use function mkdir;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

class RenameTest extends TestCase
{
    private string $tmpPath;
    private string $origFile;
    private string $oldFile;
    private string $newFile;
    private string $newDir;
    private string $newDirFile;

    /**
     * Sets the path to test files
     */
    public function setUp(): void
    {
        $control       = sprintf('%s/_files/testfile.txt', dirname(__DIR__));
        $this->tmpPath = sprintf('%s%s%s', sys_get_temp_dir(), DIRECTORY_SEPARATOR, uniqid('laminasilter'));
        mkdir($this->tmpPath, 0775, true);

        $this->oldFile    = sprintf('%s%stestfile.txt', $this->tmpPath, DIRECTORY_SEPARATOR);
        $this->origFile   = sprintf('%s%soriginal.file', $this->tmpPath, DIRECTORY_SEPARATOR);
        $this->newFile    = sprintf('%s%snewfile.xml', $this->tmpPath, DIRECTORY_SEPARATOR);
        $this->newDir     = sprintf('%s%stestdir', $this->tmpPath, DIRECTORY_SEPARATOR);
        $this->newDirFile = sprintf('%s%stestfile.txt', $this->newDir, DIRECTORY_SEPARATOR);

        copy($control, $this->oldFile);
        copy($control, $this->origFile);
        mkdir($this->newDir, 0775, true);
    }

    /**
     * Sets the path to test files
     */
    public function tearDown(): void
    {
        if (is_dir($this->tmpPath)) {
            if (file_exists($this->oldFile)) {
                unlink($this->oldFile);
            }
            if (file_exists($this->origFile)) {
                unlink($this->origFile);
            }
            if (file_exists($this->newFile)) {
                unlink($this->newFile);
            }
            if (is_dir($this->newDir)) {
                if (file_exists($this->newDirFile)) {
                    unlink($this->newDirFile);
                }
                rmdir($this->newDir);
            }
            rmdir($this->tmpPath);
        }
    }

    /**
     * Test single parameter filter
     */
    public function testConstructSingleValue(): void
    {
        $filter = new FileRename(['target' => $this->newFile]);
        self::assertSame($this->newFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /**
     * Test single parameter filter
     */
    public function testConstructSingleValueWithFilesArray(): void
    {
        $filter = new FileRename(['target' => $this->newFile]);
        self::assertSame(
            ['tmp_name' => $this->newFile],
            $filter(['tmp_name' => $this->oldFile])
        );
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /**
     * Test single array parameter filter
     */
    public function testConstructSingleArray(): void
    {
        $filter = new FileRename([
            'source' => $this->oldFile,
            'target' => $this->newFile,
        ]);

        self::assertSame($this->newFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /**
     * Test full array parameter filter
     */
    public function testConstructFullOptionsArray(): void
    {
        $filter = new FileRename([
            'source'    => $this->oldFile,
            'target'    => $this->newFile,
            'overwrite' => true,
            'randomize' => false,
            'unknown'   => false,
        ]);

        self::assertSame($this->newFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /**
     * Test single array parameter filter
     */
    public function testConstructDoubleArray(): void
    {
        $filter = new FileRename([
            0 => [
                'source' => $this->oldFile,
                'target' => $this->newFile,
            ],
        ]);

        self::assertSame($this->newFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /**
     * Test single array parameter filter
     */
    public function testConstructTruncatedTarget(): void
    {
        $filter = new FileRename([
            'source' => $this->oldFile,
        ]);

        self::assertSame($this->oldFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /**
     * Test single array parameter filter
     */
    public function testConstructTruncatedSource(): void
    {
        $filter = new FileRename([
            'target' => $this->newFile,
        ]);

        self::assertSame($this->newFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /**
     * Test single parameter filter by using directory only
     */
    public function testConstructSingleDirectory(): void
    {
        $filter = new FileRename(['target' => $this->newDir]);

        self::assertSame($this->newDirFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /**
     * Test single array parameter filter by using directory only
     */
    public function testConstructSingleArrayDirectory(): void
    {
        $filter = new FileRename([
            'source' => $this->oldFile,
            'target' => $this->newDir,
        ]);

        self::assertSame($this->newDirFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /**
     * Test single array parameter filter by using directory only
     */
    public function testConstructDoubleArrayDirectory(): void
    {
        $filter = new FileRename([
            0 => [
                'source' => $this->oldFile,
                'target' => $this->newDir,
            ],
        ]);

        self::assertSame($this->newDirFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /**
     * Test single array parameter filter by using directory only
     */
    public function testConstructTruncatedSourceDirectory(): void
    {
        $filter = new FileRename([
            'target' => $this->newDir,
        ]);

        self::assertSame($this->newDirFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    public function testAddSameFileAgainAndOverwriteExistingTarget(): void
    {
        $filter = new FileRename([
            'source' => $this->oldFile,
            'target' => $this->newFile,
        ]);

        self::assertSame($this->newFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    public function testAddFileWithString(): void
    {
        $filter = new FileRename(['target' => $this->newFile]);

        self::assertSame($this->newFile, $filter($this->oldFile));
        self::assertSame('falsefile', $filter('falsefile'));
    }

    /** @return list<array{0: mixed}> */
    public static function returnUnfilteredDataProvider(): array
    {
        $tmpPath = sprintf('%s%s%s', sys_get_temp_dir(), DIRECTORY_SEPARATOR, uniqid('returnUnfilteredDataProvider'));
        mkdir($tmpPath, 0775, true);

        $oldFile  = sprintf('%s%stestfile.txt', $tmpPath, DIRECTORY_SEPARATOR);
        $origFile = sprintf('%s%soriginal.file', $tmpPath, DIRECTORY_SEPARATOR);

        return [
            [null],
            [new stdClass()],
            [
                [
                    $oldFile,
                    $origFile,
                ],
            ],
        ];
    }

    #[DataProvider('returnUnfilteredDataProvider')]
    public function testReturnUnfiltered(mixed $input): void
    {
        $filter = new FileRename(['target' => $this->newFile]);

        self::assertSame($input, $filter($input));
    }
}
