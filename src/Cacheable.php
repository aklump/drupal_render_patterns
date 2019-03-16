<?php

namespace Drupal\render_patterns;

/**
 * A class to hold a cacheable value.
 */
class Cacheable {

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
   * @return \Drupal\render_patterns\Cacheable
   *   A new instance with embedded $value.
   */
  public static function value($value): Cacheable {
    $instance = new static();
    $instance->value = $value;
    return $instance;
  }

}
