# Changelog

## [8.x-2.0] - 2020-02-14

### Changed

- Switched to semantic versioning.
  
### Removed

- Dependency on [drupal:data_api](https://www.drupal.org/project/data_api).  See documentation _update-v2.md_ on how to handle this breaking change.

## 8.x-1.1-rc1 2019-03-15T16:16, aklump

* You must declare all properties as protected or in the schema.
* Change all your patterns to extend `\Drupal\render_patterns\Pattern` instead of `RenderPatternsPattern`.
* You must add the PHP 7 typehints for: `build` and `render` per `\Drupal\render_patterns\PatternInterface`.
* It is no longer recommended to suffix your classes with `RenderPattern`.
* You may now pass an array of values as the second argument to `render_patterns_get` to set those properties on instantiation.
* `public static defaults()` has been deprecated.  You should migrate to `class::$properties`.
* Added JSONSchema validation via `Pattern::$properties`.  Use it to define the property schema per [JSON Schema format](https://json-schema.org/latest/json-schema-validation.html) for automatic validation.

## 7.x-1.1-rc5

* BREAKING CHANGE: `defaults()` should now be a `public static` function.
