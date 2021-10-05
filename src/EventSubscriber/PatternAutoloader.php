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
 * Ensure theme autoloading works in all cases.
 */
final class PatternAutoloader implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

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
      // Load our classes before anything else, so they are available.
      KernelEvents::REQUEST => ['registerPatternClasses', 1000],
      ConfigEvents::SAVE => 'handleCachedPatternsList',
    ];
  }

  /**
   * Ensures all themes providing render patterns are added to the autoloader.
   */
  public function registerPatternClasses() {
    if ($cached = $this->cache->get('render_patterns_list')) {
      $patterns_info = $cached->data;
    }
    else {

      // Search all themes to see if they provide render_patterns, if they do
      // cache the autoloading information.  The core's autoloader doesn't not
      // seem to add theme autoloading earlier enough for some custom
      // controllers, which is why we have to do this here.  In other cases,
      // this is going to be redundant, but harmlessly so.
      $patterns_info = [];
      $themes = \Drupal::service('theme_handler')->listInfo();
      foreach ($themes as $theme) {
        $name = $theme->getName();
        $path = dirname($theme->getExtensionPathname());
        if (is_dir(\Drupal::root() . '/' . $path . '/src/RenderPatterns')) {
          $patterns_info[] = ['Drupal\\' . $name . '\\', \Drupal::root() . '/' . $path . '/src/'];
        }
      }
      $this->cache->set('render_patterns_list', $patterns_info);
    }
    foreach ($patterns_info as $info) {
      list($namespace, $directory) = $info;
      $this->classLoader->addPsr4($namespace, $directory);
    }
  }

  /**
   * When the system default theme changes we have to delete the cached list.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   An event instance.
   */
  public function handleCachedPatternsList(ConfigCrudEvent $event) {
    if ($event->getConfig()->getName() === 'core.extension') {
      $this->cache->delete('render_patterns_list');
    }
  }

}
