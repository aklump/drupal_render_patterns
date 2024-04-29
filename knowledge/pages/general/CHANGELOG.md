<!--
id: changelog
tags: ''
-->

# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
- lorem

## [4.0.0] - 2021-10-04

### Added
- Standard namespaces so core auto-loading is used.

### Changed
- Pattern classes should now be namespaced to their extension, like other classes.  For example of your theme `foo_theme` is providing classes they should be namespaced to `Drupal\foo_theme\RenderPatterns\`.  They should be saved to _foo_theme/src/RenderPatterns/_.  The same is true for modules that provide render pattern(s).

### Removed
- `render_patterns_get`
- `hook_render_patterns_info`
- `hook_render_patterns_info_alter`
- `\Drupal\render_patterns\Uncacheable`

## [8.x-3.0] - 2021-03-29

### Added

- Pattern classes should be declared `final`.
- The `::get()` method to replace `render_patterns_get()`.
- Invalidate `\Drupal::cache('bootstrap')->get('render_patterns_list')` when
  default theme changes.
- Composer-based dependencies. See _README.md_ for adding to your app-level _composer.json_ for dependencies.

### Changed

- It is no longer necessary to call `parent::__construct()` when
  using `\Drupal\Core\DependencyInjection\ContainerInjectionInterface` in your
  patterns.
- Testing a pattern property with isset() will now return FALSE if the
  overridden value is NULL.
- Moved default theme render patterns directory from _{theme}/render_patterns_ to _{theme}/src/render_patterns/_

### Removed

- `::cl()` has been removed. Try `\Drupal\front_end_components\BemTrait`
  instead.
- `::ajaxWrap()`
- `::getForm()`
- `::defaults()`
- `::render()` has been remove. Use the render service after `::build()`.

### Deprecated

- `$this->properties`. You should migrate to `::getProperties()`.
- `render_patterns_get()`. You should call `::get` on the pattern class itself.

## [8.x-2.1] - 2021-03-26

### Added

- Support for validation of objects by FQN. See docs for usage example.

## [8.x-2.0] - 2020-02-14

### Changed

- Switched to semantic versioning.

### Removed

- Dependency on [drupal:data_api](https://www.drupal.org/project/data_api). See
  documentation _update-v2.md_ on how to handle this breaking change.

## 8.x-1.1-rc1 2019-03-15T16:16, aklump

* You must declare all properties as protected or in the schema.
* Change all your patterns to extend `\Drupal\my_theme\RenderPatterns\` instead
  of `RenderPatternsPattern`.
* You must add the PHP 7 typehints for: `build` and `render`
  per `\Drupal\my_theme\RenderPatterns\Interface`.
* It is no longer recommended to suffix your classes with `RenderPattern`.
* You may now pass an array of values as the second argument
  to `render_patterns_get` to set those properties on instantiation.
* `public static defaults()` has been deprecated. You should migrate
  to `class::$properties`.
* Added JSONSchema validation via `Pattern::$properties`. Use it to define the
  property schema
  per [JSON Schema format](https://json-schema.org/latest/json-schema-validation.html)
  for automatic validation.

## 7.x-1.1-rc5

* BREAKING CHANGE: `defaults()` should now be a `public static` function.
