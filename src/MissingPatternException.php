<?php

namespace Drupal\render_patterns;

/**
 * Throws when a pattern class is not found.
 */
class MissingPatternException extends \Exception {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $pattern_classes) {
    $message = "The pattern class cannot be located as any of: " . implode(', ', $pattern_classes);
    parent::__construct($message);
  }

}
