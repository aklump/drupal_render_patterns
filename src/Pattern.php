<?php

namespace Drupal\render_patterns;

use AKlump\Data\DataInterface;
use Drupal\data_api\DataTrait;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

/**
 * Represents a Pattern object class.
 *
 * @brief An abstract base for all Pattern classes.
 */
abstract class Pattern implements PatternInterface {

  use DataTrait;

  protected $vars = [];

  /**
   * The hardcoded schema array.
   *
   * Most likely, you will use $this->properties instead.
   *
   * @var array
   */
  protected $schema;

  /**
   * The hardcoded properties section of the JSON schema.
   *
   * @var array
   */
  protected $properties;

  /**
   * An instance of the JSON schema validator.
   *
   * @var \JsonSchema\Validator
   */
  protected $validator;

  /**
   * Holds cached data.
   *
   * @var array
   */
  protected $cache;

  /**
   * RenderPatternsPattern constructor.
   */
  public function __construct(DataInterface $dataApiData) {
    $this->setDataApiData($dataApiData);
    $this->validator = new Validator();
  }

  /**
   * Verify that $key can be set as $value.
   *
   * @param string $key
   *   The property.
   * @param mixed $value
   *   The value to be set on the property.
   *
   * @throws \InvalidArgumentException
   *   If the $key is not an allowed property.
   * @throws \JsonSchema\Exception\ValidationException
   *   If the schema validation fails.
   */
  public function validateAgainstSchema(string $key, $value): void {
    $schema = $this->getSchema();

    // Verify $key is allowed to be set.
    $allowed_properties = array_keys($schema['properties'] ?? []);
    if (!in_array($key, $allowed_properties)) {
      throw new \InvalidArgumentException("\"$key\" is not an allowed property for " . static::class);
    }

    $data = (object) [
      $key => $value,
    ];

    // When validating a single key we have no concept of the dataset so, we
    // have to ignore the required part of the schema.
    unset($schema['required']);
    $this->validator->validate(
      $data,
      $schema,
      Constraint::CHECK_MODE_EXCEPTIONS
    );
  }

  public static function defaults() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function __get($key) {
    if (!in_array($key, get_class_vars(static::class))) {
      $get_method = "get__$key";
      $has_dynamic_method = method_exists($this, $get_method);
      $is_overridden = array_key_exists($key, $this->vars);
      $override_value = $this->vars[$key] ?? NULL;
      if (!$has_dynamic_method && $is_overridden) {
        return $override_value;
      }

      // Determine the default because we don't have an overridden value.
      $default_method = "default__$key";
      $default_value = $this->getSchema()['properties'][$key]['default'] ?? NULL;
      if (method_exists($this, $default_method)) {
        $default_value = $this->{$default_method}($default_value);

        // Cache this value if we are asked.
        if ($default_value instanceof Cacheable) {
          $this->vars[$key] = $default_value->value;
          $default_value = $default_value->value;
        }
      }

      // Check for a method on the class.
      if ($has_dynamic_method) {
        $dynamic_value = $this->{$get_method}($override_value, $default_value, $is_overridden);

        // Cache this value if we are asked.
        if ($dynamic_value instanceof Cacheable) {
          $this->vars[$key] = $dynamic_value->value;
          $dynamic_value = $dynamic_value->value;
        }

        return $dynamic_value;
      }

      return $is_overridden ? $override_value : $default_value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __set($key, $value) {
    if (!in_array($key, get_class_vars(static::class))) {
      $this->validateAgainstSchema($key, $value);
      $this->vars[$key] = $value;
    }
  }

  public function __isset($key) {
    // TODO Rewrite this.
    return array_key_exists($key, $this->defaults());
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return \Drupal::service("renderer")->render($this->build);
  }

  /**
   * Return a SMACSS [cl]ass name based on $this->module.
   *
   * @param string|array $name If this is an array, each element will
   *                                  be converted to a class.
   * @param bool $isComponent If it's not a component (base__thing)
   *                                  then it's a style (base--style).  This
   *                                  determines the character used to glue
   *                                  the $name to the module.
   *
   * @return string
   */
  protected function cl($name = '', $isComponent = TRUE) {
    $names = is_array($name) ? $name : [$name];
    $glue = $isComponent ? '_' : '-';
    $classes = [];
    foreach ($names as $name) {
      $classes[] = $this->module . ($name ? str_repeat($glue, 2) . $name : '');
    }

    return implode(' ', $classes);
  }


  /**
   * Adds an ajax content wrapper around $element.
   *
   * @param $element
   * @param $name
   */
  protected function ajaxWrap(&$element, $name) {
    $category = $this->cl($name);
    $class = $category . '__ajax-content';
    $ajax = [
      'content' => [
        0 => [
          '#prefix' => '<div class="' . $class . '">',
          '#suffix' => '</div>',
        ],
        '#role' => 'content',
        '#selector' => ".$class",
        '#class' => $class,
      ],
    ];

    $temp = $ajax['content'][0];
    $temp[] = $element;
    $temp['#ajax_elements'] = $ajax;
    $element = $temp;
  }

  /**
   * Use this instead of drupal_get_form when inserting forms during build().
   *
   * This will prevent an odd ajax error that will submit the form twice when
   * building during an ajax response.  Essentially, if you try to build a
   * form when $_POST has a value, then the form appears as if it's already
   * been submitted.
   *
   * @param $form_id
   *
   * @return array
   */
  protected function getForm($form_id) {
    // TODO This has not been ported to D8 yet.
    $args = func_get_args();
    $stash = $_POST;
    $_POST = [];
    $form = call_user_func_array('drupal_get_form', $args);
    $_POST = $stash;

    return $form;
  }

  /**
   * Return the schema to use for validation or processing.
   *
   * This will be read in from $this->properties or $this->schema.
   *
   * @return array
   *   The schema for this render pattern.
   */
  protected function getSchema() {
    if (empty($this->cache['schema'])) {
      if (!empty($this->schema)) {
        $this->cache['schema'] = $this->schema;
      }

      elseif (!empty($this->properties)) {
        $this->cache['schema'] = ['properties' => $this->properties];
      }

      // Support the legacy method ::defaults().
      elseif (method_exists($this, 'defaults')) {
        $this->cache['schema']['properties'] = [];
        foreach ($this->defaults() as $key => $default) {
          $this->cache['schema']['properties'][$key] = [
            'type' => gettype($default),
            'default' => $default,
          ];
        }
      }
    }

    return $this->cache['schema'];
  }

}
