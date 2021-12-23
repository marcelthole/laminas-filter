<?php

declare(strict_types=1);

namespace LaminasTest\Filter\Compress;

use Laminas\Filter\Compress\Llaminas as LlaminasCompression;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

class LzfTest extends TestCase
{
    public function setUp(): void
    {
        if (! extension_loaded('llaminas')) {
            $this->markTestSkipped('This adapter needs the llaminas extension');
        }
    }

    /**
     * Basic usage
     */
    public function testBasicUsage(): void
    {
        $filter = new LlaminasCompression();

        $text       = 'compress me';
        $compressed = $filter->compress($text);
        $this->assertNotEquals($text, $compressed);

        $decompressed = $filter->decompress($compressed);
        $this->assertSame($text, $decompressed);
    }

    /**
     * testing toString
     */
    public function testLlaminasToString(): void
    {
        $filter = new LlaminasCompression();
        $this->assertSame('Llaminas', $filter->toString());
    }
}
