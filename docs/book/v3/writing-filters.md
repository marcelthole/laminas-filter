# Writing Filters

`Laminas\Filter` supplies a set of commonly needed filters, but developers will
often need to write custom filters for their particular use cases.
You can do so by writing classes that implement `Laminas\Filter\FilterInterface`, which defines two methods, `filter()` and `__invoke()`.

## Example

```php
namespace Application\Filter;

use Laminas\Filter\FilterInterface;

class MyFilter implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        // perform some transformation upon $value to arrive at $valueFiltered

        return $valueFiltered;
    }
    
    public function __invoke(mixed $value): mixed {
        return $this->filter($value);    
    }
}
```

To attach an instance of the filter defined above to a filter chain:

```php
$filterChain = new Laminas\Filter\FilterChain($pluginManager);
$filterChain->attach(new Application\Filter\MyFilter());
```

## Registering Custom Filters with the Plugin Manager

In both Laminas MVC and Mezzio applications, the top-level `filters` configuration key can be used to register filters with the plugin manager in standard Service Manager format:

```php
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'filters' => [
        'factories' => [
            My\Filter\FilterOne::class => InvokableFactory::class,
            My\Filter\FilterTwo::class => My\Filter\SomeCustomFactory::class,
        ],
        'aliases' => [
            'filterOne' => My\Filter\FilterOne::class,
            'filterTwo' => My\Filter\FilterTwo::class,
        ],
    ],
];
```

Assuming the configuration above is merged into your application configuration, either by way of a dedicated configuration file, or via an MVC Module class or Mezzio Config Provider, you would be able to retrieve filter instances from the plugin manager by FQCN or alias.
