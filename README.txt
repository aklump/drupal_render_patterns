                         Drupal Module: Render Patterns

   Author: Aaron Klump [1]sourcecode@intheloftstudios.com

Summary

   The Render Patterns module allows you to encapsulate Drupal render
   arrays as PHP classes, for repeat use. You expose only the dynamic
   elements of your render array as class properties, and the rest of the
   render array is hidden within the black box of the render pattern
   class. This type of design makes sense if you need to reference the
   same render array in more than one place as it avoids errors caused by
   code duplication. It comes from the [2]DRY principle.

   You may also visit the [3]project page on Drupal.org.

Installation

    1. Add the following to the application's composer.json above web
       root.
{
  "repositories": [
    {
      "type": "github",
      "url": "https://github.com/aklump/drupal_render_patterns"
    }
  ]
}

    2. Now run composer require aklump_drupal/render_patterns
    3. Enable this module.
    4. Begin creating one or more render patterns in {active
       theme}/src/RenderPatterns/. (You may also provide classes in a
       module by adjusting the namespace to the module.)
    5. Use namespace \Drupal\my_theme\src\RenderPatterns for the classes.

Usage

                               Pattern Properties

   The "render pattern" is meant to encapsulate common render array
   situations. The pattern is a class with a build() method. As shown
   immediately below, nothing changes across implementations. This may not
   always be practical, so...
namespace Drupal\my_theme\RenderPatterns;

final MyReuseablePattern extends \Drupal\render_patterns\Pattern {

  public function build(): array {
    return ['#markup' => 'I am reusable text.'];
  }

  ...

}

   ...the interface defines the getProperties method, which exposes any
   number of configurable properties that will influence the build. This
   method returns an array of property names, each defining itself with
   the following keys: type, default, alter.
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

Building The Render Array

   Most often you will follow this simple pattern:
$renderable_array = \Drupal\my_theme\RenderPatterns\MyReuseablePattern::get([
  'entity' => $account,
  'ajaxContext' => ['foo' => 'bar'],
])->build();
$html = \Drupal::service('renderer')->render($renderable_array);

Instance Property Modification

   For more complete situations you have the ability to modify properties
   on an instance if you do something like this:
$pattern = \Drupal\my_theme\RenderPatterns\MyReuseablePattern::get([
  'entity' => $account,
  'ajaxContext' => ['foo' => 'bar'],
]);

$pattern->entity = $node;

$renderable_array = $pattern->build();
...

Property Validation

   Property values will be validated against the schema defined by
   getProperties() and \Drupal\my_theme\RenderPatterns\Exception will be
   thrown if the value falls outside of the allowed type. Validation uses
   [4]JSON Schema, which receives a schema built from getProperties() with
   a few, minor modifications for compatibility with Drupal.

Contact

     * In the Loft Studios
     * Aaron Klump - Developer
     * PO Box 29294 Bellingham, WA 98228-1294
     * skype: intheloftstudios
     * d.o: aklump
     * [5]http://www.InTheLoftStudios.com

References

   1. mailto:sourcecode@intheloftstudios.com
   2. https://en.wikipedia.org/wiki/Don%27t_repeat_yourself
   3. http://www.drupal.org/project/render_patterns
   4. https://json-schema.org/latest/json-schema-validation.html
   5. http://www.InTheLoftStudios.com/
