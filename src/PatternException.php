<?php

namespace Drupal\render_patterns;

/**
 * Throws when a pattern class is not found.
 */
class PatternException extends \Exception {

  /**
   * RenderPatternsInvalidPropertyException constructor.
   *
   * @param string $pattern_class
   *   The name of the pattern class being validated.
   * @param string $message
   *   The exception message.
   * @param \Exception $exception
   *   The exception that was thrown.
   */
  public function __construct($pattern_class, string $message, \Exception $exception = NULL) {
    $code = isset($exception) ? $exception->getCode() : NULL;
    parent::__construct("$pattern_class: " . $message, $code, $exception);
  }

}
