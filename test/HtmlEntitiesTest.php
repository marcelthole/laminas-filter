<?php

declare(strict_types=1);

namespace LaminasTest\Filter;

use Laminas\Filter\Exception\DomainException;
use Laminas\Filter\HtmlEntities as HtmlEntitiesFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;

use function file_get_contents;
use function strlen;

use const ENT_COMPAT;
use const ENT_NOQUOTES;
use const ENT_QUOTES;

class HtmlEntitiesTest extends TestCase
{
    /**
     * Ensures that the filter follows expected behavior
     */
    public function testBasic(): void
    {
        $valuesExpected = [
            'string' => 'string',
            '<'      => '&lt;',
            '>'      => '&gt;',
            '\''     => '&#039;',
            '"'      => '&quot;',
            '&'      => '&amp;',
        ];
        $filter         = new HtmlEntitiesFilter();
        foreach ($valuesExpected as $input => $output) {
            self::assertSame($output, $filter($input));
        }
    }

    /**
     * Ensure that fluent interfaces are supported
     */
    #[Group('Laminas-3172')]
    public function testFluentInterface(): void
    {
        $instance = new HtmlEntitiesFilter([
            'encoding'    => 'UTF-8',
            'quotestyle'  => ENT_QUOTES,
            'doublequote' => false,
        ]);
        self::assertInstanceOf(HtmlEntitiesFilter::class, $instance);
    }

    /**
     * Ensures that when ENT_QUOTES is set, the filtered value has both 'single' and "double" quotes encoded
     */
    #[Group('Laminas-8962')]
    public function testQuoteStyleQuotesEncodeBoth(): void
    {
        $input  = "A 'single' and " . '"double"';
        $result = 'A &#039;single&#039; and &quot;double&quot;';

        $filter = new HtmlEntitiesFilter(['quotestyle' => ENT_QUOTES]);
        self::assertSame($result, $filter->filter($input));
    }

    /**
     * Ensures that when ENT_COMPAT is set, the filtered value has only "double" quotes encoded
     */
    #[Group('Laminas-8962')]
    public function testQuoteStyleQuotesEncodeDouble(): void
    {
        $input  = "A 'single' and " . '"double"';
        $result = "A 'single' and &quot;double&quot;";

        $filter = new HtmlEntitiesFilter(['quotestyle' => ENT_COMPAT]);
        self::assertSame($result, $filter->filter($input));
    }

    /**
     * Ensures that when ENT_NOQUOTES is set, the filtered value leaves both "double" and 'single' quotes un-altered
     */
    #[Group('Laminas-8962')]
    public function testQuoteStyleQuotesEncodeNone(): void
    {
        $input  = "A 'single' and " . '"double"';
        $result = "A 'single' and " . '"double"';

        $filter = new HtmlEntitiesFilter(['quotestyle' => ENT_NOQUOTES]);
        self::assertSame($result, $filter->filter($input));
    }

    #[Group('Laminas-11344')]
    public function testCorrectsForEncodingMismatch(): void
    {
        $filter = new HtmlEntitiesFilter();
        $string = file_get_contents(__DIR__ . '/_files/latin-1-text.txt');
        $result = $filter->filter($string);
        self::assertGreaterThan(0, strlen($result));
    }

    #[Group('Laminas-11344')]
    public function testStripsUnknownCharactersWhenEncodingMismatchDetected(): void
    {
        $filter = new HtmlEntitiesFilter();
        $string = file_get_contents(__DIR__ . '/_files/latin-1-text.txt');
        $result = $filter->filter($string);
        self::assertStringContainsString('&quot;&quot;', $result);
    }

    #[Group('Laminas-11344')]
    public function testRaisesExceptionIfEncodingMismatchDetectedAndFinalStringIsEmpty(): void
    {
        $filter = new HtmlEntitiesFilter();
        $string = file_get_contents(__DIR__ . '/_files/latin-1-dash-only.txt');
        $this->expectException(DomainException::class);
        $filter->filter($string);
    }

    /** @return list<array{0: mixed}> */
    public static function returnUnfilteredDataProvider(): array
    {
        return [
            [null],
            [new stdClass()],
            [
                [
                    '<',
                    '>',
                ],
            ],
        ];
    }

    #[DataProvider('returnUnfilteredDataProvider')]
    public function testReturnUnfiltered(mixed $input): void
    {
        $filter = new HtmlEntitiesFilter();
        self::assertSame($input, $filter->filter($input));
    }
}
