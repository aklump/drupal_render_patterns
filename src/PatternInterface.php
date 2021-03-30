<?php

namespace Drupal\render_patterns;

/**
 * Represents a Pattern object class.
 *
 * @brief The interface for all Pattern classes.
 */
interface PatternInterface {

  /**
   * Retrieve a JSON schema for the pattern.
   *
   * @return array
   *   The JSON schema for this pattern.
   */
  public function getSchema(): array;

  /**
   * Return a render array.
   *
   * @return array
   *   The render array representing this pattern.
   */
  public function build(): array;

  /**
   * Return a new instance with properties set.
   *
   * @param array $values
   *   An array of property values.
   *
   * @return \Drupal\render_patterns\PatternInterface
   *   Self for chaining.
   */
  public static function get(array $values = []): self;

}
