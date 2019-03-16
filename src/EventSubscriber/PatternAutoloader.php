<?php

namespace Drupal\render_patterns\EventSubscriber;

use Drupal\Component\Utility\SortArray;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds autoloading of pattern classes to the bootstrap.
 */
class PatternAutoloader implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // It appears to me KernelEvents::CONTROLLER_ARGUMENTS is the last event
    // where we can pull this off before the classes are not available to our
    // view builders.
    return [
      KernelEvents::CONTROLLER_ARGUMENTS => [
        'registerPatternClasses',
        0,
      ],
    ];
  }

  /**
   * Add all render pattern classes to the autoloader.
   *
   * This will also cache the implementations of hook_render_patterns_info in a
   * list in the bootstrap cache bin.
   */
  public function registerPatternClasses() {
    if ($cached = \Drupal::cache('bootstrap')->get('render_patterns_list')) {
      $patterns_info = $cached->data;
    }
    else {

      // If we don't have a cached pattern list, we'll run our hook info and
      // compile it.  Then cache it to bootstrap bin.
      $patterns_info = [];
      $module_handler = \Drupal::moduleHandler();
      $implementations = $module_handler
        ->getImplementations('render_patterns_info');

      foreach ($implementations as $implementing_name) {
        $weight = 0;
        $info = $module_handler
          ->invoke($implementing_name, 'render_patterns_info');
        if (!isset($info['weight'])) {
          $weight = \Drupal::config('core.extension')
            ->get('module.' . $implementing_name);
        }
        $patterns_info[$implementing_name] = $info + ['weight' => $weight];
      }

      // Allow others to alter this list.
      $module_handler->alter('render_patterns_info', $patterns_info);

      // Put in weight order, after the alter has processed.
      uasort($patterns_info, [SortArray::class, 'sortByWeightElement']);

      \Drupal::cache('bootstrap')->set('render_patterns_list', $patterns_info);
    }

    // Register all our directories as PSR-4 namespaces.
    $loader = \Drupal::service('class_loader');
    foreach ($patterns_info as $info) {
      $loader->addPsr4('Drupal\\render_patterns\\Pattern\\', $info['directory']);
    }
  }

}
