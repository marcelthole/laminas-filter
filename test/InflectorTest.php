<?php

declare(strict_types=1);

namespace LaminasTest\Filter;

use Laminas\Filter\Exception;
use Laminas\Filter\Inflector as InflectorFilter;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StringToUpper;
use Laminas\Filter\Word\CamelCaseToDash;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use PHPUnit\Framework\TestCase;

use function sprintf;

use const DIRECTORY_SEPARATOR;

class InflectorTest extends TestCase
{
    public function testFilterTransformsStringAccordingToRules(): void
    {
        $filter = new InflectorFilter([
            'target' => ':controller/:action.:suffix',
            'rules'  => [
                ':controller' => [CamelCaseToDash::class],
                ':action'     => [CamelCaseToDash::class],
                'suffix'      => 'phtml',
            ],
        ]);

        $filtered = $filter([
            'controller' => 'FooBar',
            'action'     => 'bazBat',
        ]);
        self::assertSame('Foo-Bar/baz-Bat.phtml', $filtered);
    }

    public function testTargetReplacementIdentifierWorksWhenInflected(): void
    {
        $inflector = new InflectorFilter([
            'target'                      => '?=##controller/?=##action.?=##suffix',
            'rules'                       => [
                ':controller' => [CamelCaseToDash::class],
                ':action'     => [CamelCaseToDash::class],
                'suffix'      => 'phtml',
            ],
            'targetReplacementIdentifier' => '?=##',
        ]);

        $filtered = $inflector([
            'controller' => 'FooBar',
            'action'     => 'bazBat',
        ]);

        self::assertSame('Foo-Bar/baz-Bat.phtml', $filtered);
    }

    public function testTargetExceptionThrownWhenTargetSourceNotSatisfied(): void
    {
        $inflector = new InflectorFilter([
            'target'                      => '?=##controller/?=##action.?=##suffix',
            'rules'                       => [
                ':controller' => [CamelCaseToDash::class],
                ':action'     => [CamelCaseToDash::class],
                'suffix'      => 'phtml',
            ],
            'throwTargetExceptionsOn'     => true,
            'targetReplacementIdentifier' => '?=##',
        ]);

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('perhaps a rule was not satisfied');
        $inflector(['controller' => 'FooBar']);
    }

    public function testTargetExceptionNotThrownOnIdentifierNotFollowedByCharacter(): void
    {
        $inflector = new InflectorFilter([
            'target'                      => 'e:\path\to\:controller\:action.:suffix',
            'rules'                       => [
                ':controller' => [CamelCaseToDash::class, StringToLower::class],
                ':action'     => [CamelCaseToDash::class],
                'suffix'      => 'phtml',
            ],
            'throwTargetExceptionsOn'     => true,
            'targetReplacementIdentifier' => ':',
        ]);

        $filtered = $inflector(['controller' => 'FooBar', 'action' => 'MooToo']);
        self::assertSame($filtered, 'e:\path\to\foo-bar\Moo-Too.phtml');
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            'target'                      => '$controller/$action.$suffix',
            'throwTargetExceptionsOn'     => true,
            'targetReplacementIdentifier' => '$',
            'rules'                       => [
                ':controller' => [
                    'rule1' => CamelCaseToUnderscore::class,
                    'rule2' => StringToLower::class,
                ],
                ':action'     => [
                    'rule1' => CamelCaseToDash::class,
                    'rule2' => StringToUpper::class,
                ],
                'suffix'      => 'php',
            ],
        ];
    }

    /**
     * Added str_replace('\\', '\\\\', ..) to all processedParts values to disable backreferences
     *
     * @issue Laminas-2538 Laminas_Filter_Inflector::filter() fails with all numeric folder on Windows
     */
    public function testCheckInflectorWithPregBackreferenceLikeParts(): void
    {
        $inflector = new InflectorFilter([
            'target'                      => sprintf(
                ':moduleDir%s:controller%s:action.:suffix',
                DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR
            ),
            'rules'                       => [
                ':controller' => [CamelCaseToDash::class, StringToLower::class],
                ':action'     => [CamelCaseToDash::class],
                'suffix'      => 'phtml',
                'moduleDir'   => 'C:\htdocs\public\cache\00\01\42\app\modules',
            ],
            'throwTargetExceptionsOn'     => true,
            'targetReplacementIdentifier' => ':',
        ]);

        $filtered = $inflector([
            'controller' => 'FooBar',
            'action'     => 'MooToo',
        ]);
        self::assertSame(
            $filtered,
            'C:\htdocs\public\cache\00\01\42\app\modules'
            . DIRECTORY_SEPARATOR
            . 'foo-bar'
            . DIRECTORY_SEPARATOR
            . 'Moo-Too.phtml'
        );
    }

    /**
     * @issue Laminas-2964
     */
    public function testNoInflectableTarget(): void
    {
        $inflector = new InflectorFilter([
            'target' => 'abc',
            'rules'  => [':foo' => []],
        ]);
        self::assertSame($inflector(['fo' => 'bar']), 'abc');
    }
}
