<?php

namespace Drupal\render_patterns\EventSubscriber;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds auto-loading of pattern classes to the bootstrap.
 */
class PatternAutoloader implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * @var \Symfony\Component\ClassLoader\ApcClassLoader
   */
  private $classLoader;

  /**
   * PatternAutoloader constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param $class_loader
   *   The class loader. Normally Composer's ClassLoader, as included by the
   *   front controller, but may also be decorated; e.g.,
   *   \Symfony\Component\ClassLoader\ApcClassLoader.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(
    CacheBackendInterface $cache,
    ConfigFactoryInterface $config_factory,
    $class_loader,
    ModuleHandlerInterface $module_handler
  ) {
    $this->cache = $cache;
    $this->configFactory = $config_factory;
    $this->classLoader = $class_loader;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // We have to load our classes before anything else so they are available.
      KernelEvents::REQUEST => ['registerPatternClasses', 1000],
      ConfigEvents::SAVE => 'handleCachedPatternsList',
    ];
  }

  /**
   * Add all render pattern classes to the autoloader.
   *
   * This will also cache the implementations of hook_render_patterns_info in a
   * list in the bootstrap cache bin.
   */
  public function registerPatternClasses() {
    if ($cached = $this->cache->get('render_patterns_list')) {
      $patterns_info = $cached->data;
    }
    else {

      // If we don't have a cached pattern list, we'll run our hook info and
      // compile it.  Then cache it to bootstrap bin.
      $patterns_info = [];
      $implementations = $this->moduleHandler
        ->getImplementations('render_patterns_info');
      foreach ($implementations as $implementing_name) {
        $weight = 0;
        $info = $this->moduleHandler
          ->invoke($implementing_name, 'render_patterns_info');
        if ($info) {
          if (!isset($info['weight'])) {
            $weight = $this->configFactory->get('core.extension')
              ->get('module.' . $implementing_name);
          }
          $patterns_info[$implementing_name] = $info + ['weight' => $weight];
        }
      }

      // Allow others to alter this list.
      $this->moduleHandler->alter('render_patterns_info', $patterns_info);

      // Put in weight order, after the alter has processed.
      uasort($patterns_info, [SortArray::class, 'sortByWeightElement']);

      $this->cache->set('render_patterns_list', $patterns_info);
    }

    // Register all our directories as PSR-4 namespaces.
    foreach ($patterns_info as $info) {
      $this->classLoader->addPsr4('Drupal\\render_patterns\\Pattern\\', $info['directory']);
    }
  }

  /**
   * When the system default theme changes we have to delete the cached list.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   */
  public function handleCachedPatternsList(ConfigCrudEvent $event) {
    if ($event->getConfig()->getName() === 'core.extension') {
      $this->cache->delete('render_patterns_list');
    }
  }

}
