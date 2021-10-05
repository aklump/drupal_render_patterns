<?php

namespace Drupal\render_patterns;

/**
 * A class to hold an uncacheable value.
 *
 * Wrap default values returned by default__* methods in this and the data will
 * not be cached.
 */
class Uncacheable {

  /**
   * The cacheable value.
   *
   * @var mixed
   */
  public $value;

  /**
   * Create a new cacheable value.
   *
   * @param mixed $value
   *   An arbitrary value that may be cached by the pattern.
   *
   * @return \Drupal\render_patterns\Uncacheable
   *   A new instance with embedded $value.
   */
  public static function value($value): Uncacheable {
    $instance = new static();
    $instance->value = $value;
    return $instance;
  }

}
