# Object Properties

A render pattern encapsulates the render array into a class and then exposes only those properties that may change across instances.

To set a property you have two options: set the value when you instantiate the pattern, or set it on the pattern directly.

In these examples I have defined a render pattern as a PHP class `Drupal\render_pattern\Pattern\Headline` located in my default theme's folder as _{theme dir}/render\_patterns/Headline.php_.

## Set the Property on Instantiation

    $pattern = render_patterns_get('Headline', [
      'title' => 'Hello World',
    ]);
    
## Set the Property Directly

    $pattern = render_patterns_get('Headline')
    $pattern->title = 'Hello World';

## Using `isset` and `unset`

These work a little different, but logically.

`isset($pattern->title)` will return `true` if the title has been overridden.  It will return `false`, even if the default value has a value.  So it's really answering the question, "Does title have a value other than it's default?"

`unset($pattern->title)` will remove the override value and put it back in the default state.  Incidentally this will cause a `default__*` method, whose return value was previously cached, to be called again.

## Property Validation

When you try to set a property value, it will be validated against the schema.  If the property is not allowed, or it's value is not valid then an error situation occurs.

How this manifests depends on when you're setting the property.  On instantion, as in the first example, the error is handled by drupal error messages; as well as the object returned is a markup render array with the error message.  So if you render the object, you will also see the error message.
  
When setting directly, i.e., `$pattern->title`, an exception is thrown. 

## Default Values

Default values will only be returned for a property if the property has not been set.  Once set, the default mechanisms as described below do not apply.

**Default values, whether static or dynamic, are not validated per the schema, so be careful.**

### Static Default Values

Set your default values in `pattern::$properties`, using the key `default`, something like this:
    
    protected $properties = [
      'title' => [
        'type' => 'string',
        'default' => 'Title Goes Here',
      ],
    ];      

### Dynamic Default Values

If you need to provide non-static values for a property, use a method that follows the naming convention of `default_{property}`, e.g. `default__title`.  This method will only be called if a value for the property has not been overridden.  It will be called once or always dependent upon how you return your value.

If the method returns the same value every time, it is cacheable.  Just return the default value like this.  The method will only be called the first time the property is accessed.

      protected function default__tag() {
        return 'h3';
      }

If the method must do runtime calculation and may return a different default value each time, then it is not cacheable.  You must wrap the return value in `Uncacheable::value()` as seen in the example below.  This is an obvious use case because you want `$pattern->now` to always return the current time.

      protected function default__now() {
        return Uncacheable::value(time());
      }        

## Get the Value

Getting the value goes through a series of steps.  Refer to this flowchart for more info:

![getter](images/getter.png)

### `get__* methods`

These are expensive so only use if needed.  Try to use `default__*` first, if possible.  You should only use a `get__*` method if you need to process the value every time it is retrieved.  The return value is never cached.  This may be removed in a future version pending performance impact analysis.
