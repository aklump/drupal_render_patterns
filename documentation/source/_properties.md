# Pattern Properties

The "render pattern" is meant to encapsulate common render array situations. The
pattern is a class with a `build()` method. As shown immediately below, nothing
changes across implementations. This may not always be practical, so...

```php
namespace Drupal\my_theme\RenderPatterns;

final MyReuseablePattern extends \Drupal\render_patterns\Pattern {

  public function build(): array {
    return ['#markup' => 'I am reusable text.'];
  }
  
  ...
  
}
```

...the interface defines the `getProperties` method, which exposes any number of
configurable properties that will influence the build. This method returns an
array of _property names_, each defining itself with the following
keys: `type, default, alter`.

```php
protected function getProperties(): array {
  return [
    'account' => [

      // This must be an instance of this class.
      'type' => \Drupal\Core\Session\AccountInterface::class,

      // If the value is NULL, ::default() will be called.  This will only be
      // called if the value is NULL, so generally speaking this is called
      // once.  Note that any callable can be used so you do NOT need to use
      // an anonymous function here, but instead could reference a method on
      // this class or elsewhere; and the callback receives the property name
      // as the argument.
      'default' => function () {
        return $this->currentUser;
      },

      // This optional key can be used to modify the value before it's returned
      // by the magic getter.  This is always called, each time the code calls
      // $this->account.
      'alter' => function ($value, $default, $is_overridden) {
        // TODO You may do something here to alter the property value.
      },
    ],

    // In this example you see that two types entities are allowed.
    'entity' => [
      'type' => [
        \Drupal\node\NodeInterface::class,
        \Drupal\user\UserInterface::class,
      ],
    ],

    // And then here we have some basic types.
    'collectionId' => [

      // Both integers and nulls can be set on this key.
      'type' => ['integer', 'null'],
      'default' => 10,
    ],

    // Just a simple array.
    'ajaxContext' => ['type' => 'array'],
  ];
}
```

## Building The Render Array

Most often you will follow this simple pattern:

```php
$renderable_array = \Drupal\my_theme\RenderPatterns\MyReuseablePattern::get([
  'entity' => $account,
  'ajaxContext' => ['foo' => 'bar'],
])->build();
$html = \Drupal::service('renderer')->render($renderable_array);
```

## Instance Property Modification

For more complete situations you have the ability to modify properties on an
instance if you do something like this:

```php
$pattern = \Drupal\my_theme\RenderPatterns\MyReuseablePattern::get([
  'entity' => $account,
  'ajaxContext' => ['foo' => 'bar'],
]);

$pattern->entity = $node;

$renderable_array = $pattern->build();
...
```

## Property Validation

Property values will be validated against the schema defined
by `getProperties()` and `\Drupal\my_theme\RenderPatterns\Exception` will be
thrown if the value falls outside of the allowed `type`. Validation
uses [JSON Schema](https://json-schema.org/latest/json-schema-validation.html),
which receives a schema built from `getProperties()` with a few, minor
modifications for compatibility with Drupal.
