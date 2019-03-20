<?php

/**
 * A class to hold an uncacheable value.
 *
 * Wrap default values returned by default__* methods in this and the data will
 * not be cached.
 */
class RenderPatternsUncacheable {

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
   * @return RenderPatternsUncacheable
   *   A new instance with embedded $value.
   */
  public static function value($value) {
    $instance = new static();
    $instance->value = $value;

    return $instance;
  }

}
