<?php

declare(strict_types=1);

namespace Laminas\Filter;

use Laminas\Filter\Exception\InvalidArgumentException;
use Laminas\ServiceManager\ServiceManager;

use function array_key_exists;
use function array_keys;
use function array_values;
use function class_exists;
use function is_array;
use function is_scalar;
use function is_string;
use function ltrim;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function str_replace;

/**
 * Filter chain for string inflection
 *
 * @psalm-type Options = array{
 *     target: string,
 *     rules?: array,
 *     throwTargetExceptionsOn?: bool,
 *     targetReplacementIdentifier?: string,
 *     pluginManager?: FilterPluginManager,
 * }
 * @implements FilterInterface<string>
 */
final class Inflector implements FilterInterface
{
    private readonly FilterPluginManager $pluginManager;

    private readonly string $target;

    private readonly bool $throwTargetExceptionsOn;

    private readonly string $targetReplacementIdentifier;

    private array $rules = [];

    /**
     * @param Options $options
     */
    public function __construct(array $options = [])
    {
        if (array_key_exists('pluginManager', $options)) {
            if (is_scalar($options['pluginManager']) && class_exists($options['pluginManager'])) {
                $options['pluginManager'] = new $options['pluginManager']();
            }
            $this->pluginManager = $options['pluginManager'];
        } else {
            $this->pluginManager = new FilterPluginManager(new ServiceManager());
        }

        $this->throwTargetExceptionsOn     = $options['throwTargetExceptionsOn'] ?? true;
        $this->targetReplacementIdentifier = $options['targetReplacementIdentifier'] ?? ':';
        if (! array_key_exists('target', $options)) {
            throw new InvalidArgumentException('The target option is required.');
        }
        $this->target = $options['target'];

        if (array_key_exists('rules', $options)) {
            $this->addRules($options['rules']);
        }
    }

    /**
     * Inflect
     *
     * @param  string|array $value
     * @throws Exception\RuntimeException
     */
    public function filter(mixed $value): mixed
    {
        // clean source
        foreach ((array) $value as $sourceName => $sourceValue) {
            $value[ltrim($sourceName, ':')] = $sourceValue;
        }

        $pregQuotedTargetReplacementIdentifier = preg_quote($this->targetReplacementIdentifier, '#');
        $processedParts                        = [];

        foreach ($this->rules as $ruleName => $ruleValue) {
            if (isset($value[$ruleName])) {
                if (is_string($ruleValue)) {
                    // overriding the set rule
                    $processedParts['#' . $pregQuotedTargetReplacementIdentifier . $ruleName . '#'] = str_replace(
                        '\\',
                        '\\\\',
                        $value[$ruleName]
                    );
                } elseif (is_array($ruleValue)) {
                    $processedPart = $value[$ruleName];
                    foreach ($ruleValue as $ruleFilter) {
                        $processedPart = $ruleFilter($processedPart);
                    }
                    $processedParts['#' . $pregQuotedTargetReplacementIdentifier . $ruleName . '#'] = str_replace(
                        '\\',
                        '\\\\',
                        $processedPart
                    );
                }
            } elseif (is_string($ruleValue)) {
                $processedParts['#' . $pregQuotedTargetReplacementIdentifier . $ruleName . '#'] = str_replace(
                    '\\',
                    '\\\\',
                    $ruleValue
                );
            }
        }

        // all of the values of processedParts would have been str_replace('\\', '\\\\', ..)'d
        // to disable preg_replace backreferences
        $inflectedTarget = preg_replace(array_keys($processedParts), array_values($processedParts), $this->target);

        if (
            $this->throwTargetExceptionsOn
            && preg_match('#(?=' . $pregQuotedTargetReplacementIdentifier . '[A-Za-z]{1})#', $inflectedTarget)
        ) {
            throw new Exception\RuntimeException(
                'A replacement identifier ' . $this->targetReplacementIdentifier
                . ' was found inside the inflected target, perhaps a rule was not satisfied with a target source?  '
                . 'Unsatisfied inflected target: ' . $inflectedTarget
            );
        }

        return $inflectedTarget;
    }

    public function __invoke(mixed $value): mixed
    {
        return $this->filter($value);
    }

    private function normalizeSpec(string $spec): string
    {
        return ltrim($spec, ':&');
    }

    /**
     * Resolve named filters and convert them to filter objects.
     */
    private function getRule(string $rule): FilterInterface
    {
        return $this->pluginManager->get($rule);
    }

    /**
     * Multi-call to setting filter rules.
     *
     * If prefixed with a ":" (colon), a filter rule will be added.  If not
     * prefixed, a static replacement will be added.
     *
     * ex:
     * array(
     *     ':controller' => array('CamelCaseToUnderscore', 'StringToLower'),
     *     ':action'     => array('CamelCaseToUnderscore', 'StringToLower'),
     *     'suffix'      => 'phtml'
     *     );
     */
    private function addRules(array $rules): self
    {
        $keys = array_keys($rules);
        foreach ($keys as $spec) {
            if ($spec[0] === ':') {
                $this->addFilterRule($spec, $rules[$spec]);
            } else {
                $this->setStaticRule($spec, $rules[$spec]);
            }
        }

        return $this;
    }

    /**
     * Add a filter rule for a spec
     */
    private function addFilterRule(mixed $spec, mixed $ruleSet): self
    {
        $spec = $this->normalizeSpec($spec);
        if (! isset($this->rules[$spec])) {
            $this->rules[$spec] = [];
        }

        if (! is_array($ruleSet)) {
            $ruleSet = [$ruleSet];
        }

        if (is_string($this->rules[$spec])) {
            $temp                 = $this->rules[$spec];
            $this->rules[$spec]   = [];
            $this->rules[$spec][] = $temp;
        }

        foreach ($ruleSet as $rule) {
            $this->rules[$spec][] = $this->getRule($rule);
        }

        return $this;
    }

    /**
     * Set a static rule for a spec.  This is a single string value
     */
    private function setStaticRule(string $name, string $value): self
    {
        $name               = $this->normalizeSpec($name);
        $this->rules[$name] = $value;
        return $this;
    }
}
