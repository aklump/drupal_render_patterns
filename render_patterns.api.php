<?php

/**
 * @file
 * Defines the API functions provided by the render_patterns module.
 */

/**
 * Implements hook_render_patterns_info().
 *
 * Provide information of how your module or theme is using this module.
 *
 * @return array
 *   - directory String The directory wherein to search for classes.  They will
 *   be autoloaded.
 *   - weight int Optional.  When omitted your module's system weight is used.
 *     This value is used in the autoloader registry.
 *
 * @see hook_render_patterns_info_alter()
 */
function hook_render_patterns_info() {
  return [
    'directory' => drupal_get_path('module', 'my_module') . '/src/render_patterns',
    // Give the theme the higher weight so it can override.
    'weight' => 10,
  ];
}

/**
 * Implements hook_render_patterns_info_alter().
 *
 * @see hook_render_patterns_info()
 */
function hook_render_patterns_info_alter(&$info) {

  // Change the location of the render_patterns for the default theme.
  $default_theme = \Drupal::service('config.factory')
    ->get('system.theme')
    ->get('default');
  $info['render_patterns']['directory'] = \Drupal::root() . '/' . drupal_get_path('theme', $default_theme) . '/includes/patterns';
}
