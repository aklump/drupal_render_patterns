<?php

namespace Drupal\render_patterns;

use AKlump\Data\DataInterface;

/**
 * Returns an new instance of a render pattern.
 */
class PatternFactory {

  /**
   * A data api instance.
   *
   * @var \AKlump\Data\DataInterface
   */
  protected $dataApi;

  /**
   * PatternFactory constructor.
   *
   * @param \AKlump\Data\DataInterface $data_api
   *   An instance.
   */
  public function __construct(DataInterface $data_api) {
    $this->dataApi = $data_api;
  }

  /**
   * Get a new render pattern instance.
   *
   * @param string $pattern_name
   *   The name of the render pattern.  There should be a PHP class with this
   *   name inside of a render_patterns/ folder somewhere.  The folder must be
   *   in the default theme, or registered using hook_render_patterns_info.
   * @param array $data
   *   An optional dataset to use when instantiating.
   *
   * @return \Drupal\render_patterns\PatternInterface
   *   An instance of the pattern.
   *
   * @throws \InvalidArgumentException
   *   If the dataset does not validate.
   * @throws \Drupal\render_patterns\MissingPatternException
   *   If the pattern class is not found.
   */
  public function get(string $pattern_name, array $data = []): PatternInterface {
    $candidates = array_map(function ($name) {
      return 'Drupal\\render_patterns\\Pattern\\' . $name;
    }, array_unique([
      $pattern_name . 'RenderPattern',
      $pattern_name,
    ]));

    foreach ($candidates as $class_name) {
      if (class_exists($class_name)) {
        $pattern = new $class_name($this->dataApi);
        if ($data) {
          foreach ($data as $key => $value) {
            $pattern->{$key} = $value;
          }
        }
        return $pattern;
      }
    }

    throw new MissingPatternException($candidates);
  }

}
