<?php

declare(strict_types=1);

namespace LaminasTest\Filter;

use Generator;
use Laminas\Filter\Callback;
use Laminas\Filter\DataUnitFormatter;
use Laminas\Filter\FilterPluginManager;
use Laminas\Filter\Inflector;
use Laminas\Filter\PregReplace;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Throwable;

use function assert;
use function class_exists;
use function in_array;

class FilterPluginManagerCompatibilityTest extends TestCase
{
    private const FILTERS_WITH_REQUIRED_OPTIONS = [
        Callback::class,
        DataUnitFormatter::class,
        Inflector::class,
        PregReplace::class,
    ];

    protected static function getPluginManager(): FilterPluginManager
    {
        return CreatePluginManager::withDefaults();
    }

    /** @return Generator<string, array{0: string, 1: class-string}> */
    public static function aliasProvider(): Generator
    {
        $class  = new ReflectionClass(FilterPluginManager::class);
        $config = $class->getConstant('CONFIGURATION');
        self::assertIsArray($config);
        self::assertArrayHasKey('aliases', $config);
        self::assertIsArray($config['aliases']);

        foreach ($config['aliases'] as $alias => $target) {
            self::assertIsString($alias);
            self::assertIsString($target);

            if (in_array($target, self::FILTERS_WITH_REQUIRED_OPTIONS, true)) {
                continue;
            }

            assert(class_exists($target));

            yield $alias => [$alias, $target];
        }
    }

    /**
     * @param class-string $expected
     */
    #[DataProvider('aliasProvider')]
    public function testPluginAliasesResolve(string $alias, string $expected): void
    {
        self::assertInstanceOf(
            $expected,
            self::getPluginManager()->get($alias),
            "Alias '$alias' does not resolve'",
        );
    }

    public function testLoadingInvalidElementRaisesException(): void
    {
        $manager = self::getPluginManager();
        $manager->configure([
            'factories' => [
                'test' => static fn(): stdClass => new stdClass(),
            ],
        ]);
        $this->expectException($this->getServiceNotFoundException());
        $manager->get('test');
    }

    /** @return class-string<Throwable> */
    protected function getServiceNotFoundException(): string
    {
        return InvalidServiceException::class;
    }

    public function testRegisteringInvalidElementRaisesException(): void
    {
        $manager = self::getPluginManager();
        $this->expectException($this->getServiceNotFoundException());
        /** @psalm-suppress InvalidArgument - Because we are testing an invalid argument */
        $manager->setService('test', new stdClass());
    }
}
