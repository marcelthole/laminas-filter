<?php

declare(strict_types=1);

namespace LaminasTest\Filter\Compress;

use Laminas\Filter\Compress\DefaultFileAdapterMatcher;
use Laminas\Filter\Compress\TarAdapter;
use Laminas\Filter\Compress\ZipAdapter;
use Laminas\Filter\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DefaultFileAdapterMatcherTest extends TestCase
{
    /** @return list<array{0: non-empty-string, 1: class-string}> */
    public static function matchingDataProvider(): array
    {
        return [
            ['/some/bar/baz.zip', ZipAdapter::class],
            ['/some/bar/baz.tar', TarAdapter::class],
            ['/some/bar/baz.tar.gz', TarAdapter::class],
            ['/some/bar/baz.tar.bz2', TarAdapter::class],
            ['/some/bar/baz.ZIP', ZipAdapter::class],
            ['/some/bar/baz.TAR', TarAdapter::class],
            ['/some/bar/baz.tar.GZ', TarAdapter::class],
            ['/some/bar/baz.tar.BZ2', TarAdapter::class],
            ['zip', ZipAdapter::class],
            ['tar', TarAdapter::class],
            ['tar.gz', TarAdapter::class],
            ['tar.bz2', TarAdapter::class],
            ['ZIP', ZipAdapter::class],
            ['TaR', TarAdapter::class],
            ['tar.GZ', TarAdapter::class],
            ['tar.BZ2', TarAdapter::class],
        ];
    }

    /**
     * @param non-empty-string $path
     * @param class-string $class
     */
    #[DataProvider('matchingDataProvider')]
    public function testExpectedAdapterIsReturned(string $path, string $class): void
    {
        $match   = new DefaultFileAdapterMatcher();
        $adapter = $match->matchFilenameExtension($path);

        self::assertInstanceOf($class, $adapter);
    }

    /** @return list<array{0: non-empty-string}> */
    public static function nonMatchingDataProvider(): array
    {
        return [
            ['/some/foo/file.txt'],
            ['txt'],
            ['foo.zipper'],
        ];
    }

    /**
     * @param non-empty-string $path
     */
    #[DataProvider('nonMatchingDataProvider')]
    public function testInvalidPaths(string $path): void
    {
        $match = new DefaultFileAdapterMatcher();
        $this->expectException(RuntimeException::class);
        $match->matchFilenameExtension($path);
    }
}
