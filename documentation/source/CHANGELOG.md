# Changelog

## 8.x-1.1-rc1 2019-03-15T16:16, aklump

* `public static defaults()` has been deprecated.  You should migrate over to `class::$properties` as soon as possible to take advantage of validation.
* You may now pass an array of values as the second argument to `render_patterns_get` to set those properties on instantiation.
* Added JSONSchema validation via `Pattern::$properties`.  Use it to define the property schema per [JSON Schema format](https://json-schema.org/latest/json-schema-validation.html) for automatic validation.

## 7.x-1.1-rc5

* BREAKING CHANGE: `defaults()` should now be a `public static` function.
