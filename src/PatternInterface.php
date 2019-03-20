<?php

namespace Drupal\render_patterns;

/**
 * Represents a Pattern object class.
 *
 * @brief The interface for all Pattern classes.
 */
interface PatternInterface {

  /**
   * Return a render array.
   *
   * @return array
   *   The render array representing this pattern.
   */
  public function build(): array;

  /**
   * Renders the build array using drupal_render().
   *
   * @return string
   *   The markup for this pattern instance.
   *
   * @throws RuntimeException
   *   If unabled to render.
   */
  public function render(): string;
}
