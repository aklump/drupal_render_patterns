<?php
namespace Drupal\render_patterns;

/**
 * Represents a Pattern object class.
 *
 * @brief An abstract base for all Render patterns based on a display suite
 *        layout.
 *
 * @see   ds_get_layout_info().
 */
abstract class DisplaySuitePattern extends RenderPatternsPattern {

  // Map to the ds layout by ds layout key.
  protected $ds_layout = '';

  public function defaults() {
    $layout = $this->ds_layout;
    if (!\Drupal::moduleHandler()->moduleExists('ds')) {
      throw new \RuntimeException("Missing dependency module: Display Suite");
    }
    $info = ds_get_layout_info();
    if (!isset($info[$layout])) {
      throw new \RuntimeException("Missing layout: {$layout}.");
    }

    return array_fill_keys($info[$layout]['regions'], NULL);
  }
}
