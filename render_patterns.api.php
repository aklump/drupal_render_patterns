<?php
/**
 * @file
 * Defines the API functions provided by the render_patterns module.
 *
 * @ingroup render_patterns
 * @{
 */

/**
 * Implements hook_render_patterns_info().
 *
 * Provide information of how your module or theme is using this module.
 *
 * @return  array
 *   - directory String The directory wherein to search for classes.  They will be
 *     autoloaded.
 *   - weight int Optional.  When omitted your module's system weight is used.
 *     This value is used in the autoloader registry.
 *
 * @see hook_render_patterns_info_alter().
 */
function hook_render_patterns_info() {
  return array(
    'directory' => drupal_get_path('module', 'my_module') . '/render_patterns',
    // Give the theme the higher weight so it can override.
    'weight' => 10,
  );
}

/**
 * Implements hook_render_patterns_info_alter().
 *
 * @see  hook_render_patterns_info().
 */
function hook_render_patterns_info_alter(&$info) {
  // Chenge the default location of the render_patterns dir for the default theme.
  $info['render_patterns']['directory'] = drupal_get_path('theme', variable_get('theme_default', '')) . '/includes/patterns';
}
