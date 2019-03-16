<?php
/**
 * @file
 * Defines the PatternInterface class.
 */

/**
 * Represents a Pattern object class.
 *
 * @brief The interface for all Pattern classes.
 */
interface RenderPatternsPatternInterface {

  /**
   * Return an array of default values.
   *
   * This is called only at instantiation.
   *
   * @return array
   */
  public static function defaults();

  /**
   * Return a render array
   *
   * @return [type] [description]
   */
  public function build();

  /**
   * Renders the build array using drupal_render().
   *
   * @return string
   *
   * @throws RuntimeException If unabled to render.
   */
  public function render();
}