<?php

declare(strict_types=1);

namespace LaminasTest\Filter\Word;

use Laminas\Filter\Word\SeparatorToCamelCase as SeparatorToCamelCaseFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;

class SeparatorToCamelCaseTest extends TestCase
{
    public function testFilterSeparatesCamelCasedWordsWithSpacesByDefault(): void
    {
        $string   = 'camel cased words';
        $filter   = new SeparatorToCamelCaseFilter();
        $filtered = $filter($string);

        self::assertNotEquals($string, $filtered);
        self::assertSame('CamelCasedWords', $filtered);
    }

    public function testFilterSeparatesCamelCasedWordsWithProvidedSeparator(): void
    {
        $string   = 'camel:-:cased:-:Words';
        $filter   = new SeparatorToCamelCaseFilter(['separator' => ':-:']);
        $filtered = $filter($string);

        self::assertNotEquals($string, $filtered);
        self::assertSame('CamelCasedWords', $filtered);
    }

    #[Group('Laminas-10517')]
    public function testFilterSeparatesUniCodeCamelCasedWordsWithProvidedSeparator(): void
    {
        $string   = 'camel:-:cased:-:Words';
        $filter   = new SeparatorToCamelCaseFilter(['separator' => ':-:']);
        $filtered = $filter($string);

        self::assertNotEquals($string, $filtered);
        self::assertSame('CamelCasedWords', $filtered);
    }

    #[Group('Laminas-10517')]
    public function testFilterSeparatesUniCodeCamelCasedUserWordsWithProvidedSeparator(): void
    {
        $string   = 'test šuma';
        $filter   = new SeparatorToCamelCaseFilter(['separator' => ' ']);
        $filtered = $filter($string);

        self::assertNotEquals($string, $filtered);
        self::assertSame('TestŠuma', $filtered);
    }

    #[Group('6151')]
    public function testFilterSeparatesCamelCasedNonAlphaWordsWithProvidedSeparator(): void
    {
        $string   = 'user_2_user';
        $filter   = new SeparatorToCamelCaseFilter(['separator' => '_']);
        $filtered = $filter($string);

        self::assertNotEquals($string, $filtered);
        self::assertSame('User2User', $filtered);
    }

    public function testFilterSupportArray(): void
    {
        $filter = new SeparatorToCamelCaseFilter();

        $input = [
            'camel cased words',
            'something different',
        ];

        $filtered = $filter($input);

        self::assertNotEquals($input, $filtered);
        self::assertSame(['CamelCasedWords', 'SomethingDifferent'], $filtered);
    }

    /** @return list<array{0: mixed}> */
    public static function returnUnfilteredDataProvider(): array
    {
        return [
            [null],
            [new stdClass()],
        ];
    }

    #[DataProvider('returnUnfilteredDataProvider')]
    public function testReturnUnfiltered(mixed $input): void
    {
        $filter = new SeparatorToCamelCaseFilter();

        self::assertSame($input, $filter($input));
    }

    /**
     * @return array<int|float|bool>[]
     */
    public static function returnNonStringScalarValues(): array
    {
        return [
            [1],
            [1.0],
            [true],
            [false],
        ];
    }

    #[DataProvider('returnNonStringScalarValues')]
    public function testShouldFilterNonStringScalarValues(float|bool|int $input): void
    {
        $filter = new SeparatorToCamelCaseFilter();

        self::assertSame((string) $input, $filter($input));
    }
}
