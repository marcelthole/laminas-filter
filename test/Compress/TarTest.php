<?php

declare(strict_types=1);

namespace LaminasTest\Filter\Compress;

use Archive_Tar;
use InvalidArgumentException;
use Laminas\Filter\Compress\Tar;
use Laminas\Filter\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function chmod;
use function class_exists;
use function file_get_contents;
use function is_dir;
use function mkdir;
use function sprintf;
use function trim;

class TarTest extends TestCase
{
    /** @var non-empty-string */
    private string $dir;

    protected function setUp(): void
    {
        if (! class_exists(Archive_Tar::class)) {
            self::markTestSkipped('Archive_Tar must be installed from PEAR for these tests');
        }

        $this->dir = __DIR__ . '/tmp';
        if (! is_dir($this->dir)) {
            mkdir($this->dir);
        }
    }

    protected function tearDown(): void
    {
        TmpDirectory::cleanUp($this->dir);
    }

    /** @return array<string, array{0:string|null}> */
    public static function modeProvider(): array
    {
        return [
            'bz2'  => ['bz2'],
            'gz'   => ['gz'],
            'BZ2'  => ['BZ2'],
            'GZ'   => ['GZ'],
            'Bz2'  => ['Bz2'],
            'Gz'   => ['Gz'],
            'null' => [null],
        ];
    }

    #[DataProvider('modeProvider')]
    public function testCompressedStringContentsWillBeDecompressedToTheExpectedFile(string|null $mode): void
    {
        $value      = 'Some Content';
        $archive    = $this->dir . '/test.tar';
        $expectFile = $this->dir . '/SomeFile.txt';

        self::assertFileDoesNotExist($archive);

        /** @psalm-suppress ArgumentTypeCoercion */
        $adapter = new Tar(['mode' => $mode]);
        $adapter->compressStringToFile($archive, 'SomeFile.txt', $value);

        self::assertFileExists($archive);

        $adapter->decompressArchive($archive, $this->dir);

        self::assertFileExists($expectFile);
        self::assertSame($value, file_get_contents($expectFile));
    }

    #[DataProvider('modeProvider')]
    public function testTheContentsOfADirectoryWillBeCompressed(string|null $mode): void
    {
        $target  = __DIR__ . '/fixtures/directory-to-compress';
        $archive = $this->dir . '/test.tar';
        self::assertFileDoesNotExist($archive);

        /** @psalm-suppress ArgumentTypeCoercion */
        $adapter = new Tar(['mode' => $mode]);
        $adapter->compressDirectoryContents($archive, $target);
        self::assertFileExists($archive);

        $adapter->decompressArchive($archive, $this->dir);

        $expect = [
            $this->dir . '/File1.txt',
            $this->dir . '/File2.txt',
            $this->dir . '/nested/File3.txt',
        ];

        foreach ($expect as $path) {
            self::assertFileExists($path);
        }
    }

    #[DataProvider('modeProvider')]
    public function testASingleFileCanBeCompressed(string|null $mode): void
    {
        $archive = $this->dir . '/test.tar';
        self::assertFileDoesNotExist($archive);

        /** @psalm-suppress ArgumentTypeCoercion */
        $adapter = new Tar(['mode' => $mode]);
        $adapter->compressFile($archive, __DIR__ . '/fixtures/directory-to-compress/File1.txt');

        self::assertFileExists($archive);

        $adapter->decompressArchive($archive, $this->dir);

        $expectFile = $this->dir . '/File1.txt';

        self::assertFileExists($expectFile);
        self::assertSame('File 1', trim(file_get_contents($expectFile)));
    }

    public function testCompressFileThatDoesNotExist(): void
    {
        $adapter = new Tar();
        $archive = $this->dir . '/test.tar';

        $this->expectException(InvalidArgumentException::class);

        $adapter->compressFile($archive, __DIR__ . '/not-there.txt');
    }

    public function testCompressDirectoryThatDoesNotExist(): void
    {
        $adapter = new Tar();
        $archive = $this->dir . '/test.tar';

        $this->expectException(InvalidArgumentException::class);

        $adapter->compressDirectoryContents($archive, __DIR__ . '/not-there');
    }

    public function testDecompressAnArchiveThatDoesNotExist(): void
    {
        $adapter = new Tar();
        $archive = $this->dir . '/test.tar';

        $this->expectException(InvalidArgumentException::class);

        $adapter->decompressArchive($archive, $this->dir);
    }

    /** @return non-empty-string */
    private function makeReadOnlyDirectory(): string
    {
        $target = $this->dir . '/un-writable';
        mkdir($target);
        chmod($target, 0400);

        return $target;
    }

    public function testDecompressAnArchiveToUnWritableTarget(): void
    {
        $adapter = new Tar();
        $archive = __DIR__ . '/fixtures/Archive.tar';

        $target = $this->makeReadOnlyDirectory();

        try {
            $adapter->decompressArchive($archive, $target);

            self::fail('An exception was not thrown');
        } catch (RuntimeException $e) {
            self::assertSame('Error while extracting the Tar archive', $e->getMessage());
        } finally {
            chmod($target, 0700);
        }
    }

    public function testCompressStringToUnWritableTarget(): void
    {
        $adapter = new Tar();
        $dir     = $this->makeReadOnlyDirectory();
        $archive = sprintf('%s/Test.tar', $dir);
        try {
            $adapter->compressStringToFile($archive, 'Foo.txt', 'Foo');
            self::fail('An exception was not thrown');
        } catch (RuntimeException $e) {
            self::assertSame('Error creating the Tar archive', $e->getMessage());
        } finally {
            chmod($dir, 0700);
        }
    }

    public function testCompressDirectoryToUnWritableTarget(): void
    {
        $adapter = new Tar();
        $dir     = $this->makeReadOnlyDirectory();
        $archive = sprintf('%s/Test.tar', $dir);
        try {
            $adapter->compressDirectoryContents($archive, __DIR__ . '/fixtures/directory-to-compress');
            self::fail('An exception was not thrown');
        } catch (RuntimeException $e) {
            self::assertSame('Error creating the Tar archive', $e->getMessage());
        } finally {
            chmod($dir, 0700);
        }
    }

    public function testCompressFileToUnWritableTarget(): void
    {
        $adapter = new Tar();
        $dir     = $this->makeReadOnlyDirectory();
        $archive = sprintf('%s/Test.tar', $dir);
        try {
            $adapter->compressFile($archive, __DIR__ . '/fixtures/directory-to-compress/File1.txt');
            self::fail('An exception was not thrown');
        } catch (RuntimeException $e) {
            self::assertSame('Error creating the Tar archive', $e->getMessage());
        } finally {
            chmod($dir, 0700);
        }
    }
}
