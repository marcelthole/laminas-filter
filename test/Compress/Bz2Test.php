<?php

declare(strict_types=1);

namespace LaminasTest\Filter\Compress;

use Laminas\Filter\Compress\Bz2;
use Laminas\Filter\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_map;
use function extension_loaded;
use function range;

class Bz2Test extends TestCase
{
    public function setUp(): void
    {
        if (! extension_loaded('bz2')) {
            self::markTestSkipped('This adapter needs the bz2 extension');
        }
    }

    public function testBasicUsage(): void
    {
        $adapter = new Bz2();

        $input      = 'Kermit the Frog';
        $compressed = $adapter->compress($input);
        $result     = $adapter->decompress($compressed);

        self::assertSame($input, $result);
    }

    /** @return list<array{0: int<1, 9>}> */
    public static function levelProvider(): array
    {
        /** @psalm-var list<array{0: int<1, 9>}> */
        return array_map(
            static fn (int $level): array => [$level],
            range(1, 9),
        );
    }

    #[DataProvider('levelProvider')]
    public function testThatDifferentCompressionLevelsWillNotAffectFunctionality(int $level): void
    {
        $adapter = new Bz2([
            'blocksize' => $level,
        ]);

        $input      = 'Kermit the Frog';
        $compressed = $adapter->compress($input);
        $result     = $adapter->decompress($compressed);

        self::assertSame($input, $result);
    }

    public function testInvalidCompressionLevel(): void
    {
        /** @psalm-suppress InvalidArgument */
        $adapter = new Bz2([
            'blocksize' => 99,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error during compression: Bz Error code -2');
        $adapter->compress('Foo');
    }

    public function testDecompressingInvalidContent(): void
    {
        $adapter = new Bz2();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error during decompression: Bz Error code -5');
        $adapter->decompress('Foo');
    }
}
