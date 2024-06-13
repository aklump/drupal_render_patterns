# Drupal Module: Render Patterns

**Author:** Aaron Klump  <sourcecode@intheloftstudios.com>

## Summary

The _Render Patterns_ module allows you to encapsulate Drupal render arrays as PHP classes, for repeat use. You expose only the dynamic elements of your render array as class properties, and the rest of the render array is hidden within the black box of the render pattern class. This type of design makes sense if you need to reference the same render array in more than one place as it avoids errors caused by code duplication. It comes from the [DRY principle](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself).

You may also visit the [project page](http://www.drupal.org/project/render_patterns) on Drupal.org.

## Install with Composer1. Because this is an unpublished package, you must define it's repository in
   your project's _composer.json_ file. Add the following to _composer.json_ in
   the `repositories` array:
   
    ```json
    {
        "type": "github",
        "url": "https://github.com/aklump/drupal_render_patterns"
    }
    ```
1. Require this package:
   
    ```
    composer require aklump_drupal/render_patterns:^4.2
    ```
1. Add the installed directory to _.gitignore_
   
   ```php
   /web/modules/custom/render_patterns/
   ```

4. Enable this module.
5. Begin creating one or more render patterns in _{active theme}/src/RenderPatterns/_.  (You may also provide classes in a module by adjusting the namespace to the module.)
6. Use namespace `\Drupal\my_theme\src\RenderPatterns` for the classes.

## Usage

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

### Building The Render Array

Most often you will follow this simple pattern:

```php
$renderable_array = \Drupal\my_theme\RenderPatterns\MyReuseablePattern::get([
  'entity' => $account,
  'ajaxContext' => ['foo' => 'bar'],
])->build();
$html = \Drupal::service('renderer')->render($renderable_array);
```

### Instance Property Modification

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

### Property Validation

Property values will be validated against the schema defined
by `getProperties()` and `\Drupal\my_theme\RenderPatterns\Exception` will be
thrown if the value falls outside of the allowed `type`. Validation
uses [JSON Schema](https://json-schema.org/latest/json-schema-validation.html),
which receives a schema built from `getProperties()` with a few, minor
modifications for compatibility with Drupal.
