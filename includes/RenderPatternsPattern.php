<?php

use Drupal\data_api\Data;
use Drupal\data_api\DataTrait;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents a Pattern object class.
 *
 * @brief An abstract base for all Pattern classes.
 *
 * Be sure to define either of the following on your child class, an array
 *   which is a JSON schema.
 *  - class::$properties
 *  - class::$schema
 *
 * You may use the following API methods to influence property values; see
 *   documentation for more info.
 * - protected function default__{property_name}()
 * - protected function get__{property_name}{))
 *
 * @link https://json-schema.org/latest/json-schema-validation.html
 */
abstract class RenderPatternsPattern implements RenderPatternsPatternInterface {

  use DataTrait;

  /**
   * Holds overridden values.
   *
   * @var array
   */
  protected $overrides = [];

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
  public function __construct(Data $dataApiData) {
    $this->setDataApiData($dataApiData);
    $this->validator = new Validator();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('data_api')
    );
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
  protected function validateKeyValueBySchema($key, $value) {
    $schema = $this->getSchema() + ['properties' => []];

    // Verify $key is allowed to be set.
    $allowed_properties = array_keys($schema['properties']);
    if (!in_array($key, $allowed_properties)) {
      throw new RenderPatternsPatternException(static::class, "\"$key\" is not an allowed property");
    }

    $data = (object) [
      $key => $value,
    ];

    // When validating a single key we have no concept of the dataset so, we
    // have to ignore the required part of the schema.
    unset($schema['required']);
    try {
      $this->validator->validate($data, $schema, Constraint::CHECK_MODE_EXCEPTIONS);
    }
    catch (\Exception $exception) {
      throw new RenderPatternsPatternException(static::class, "Property \"$key\", " . $exception->getMessage(), $exception);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __get($key) {
    $get_method = "get__$key";
    $has_dynamic_method = method_exists($this, $get_method);
    $is_overridden = array_key_exists($key, $this->overrides);
    $override_value = isset($this->overrides[$key]) ? $this->overrides[$key] : NULL;
    if (!$has_dynamic_method && $is_overridden) {
      return $override_value;
    }

    // Determine the default because we don't have an overridden value.
    $default_method = "default__$key";
    $schema = $this->getSchema();
    if (!isset($schema['properties'][$key]['type'])) {
      throw new RenderPatternsPatternException(static::class, "Incomplete schema for \"{$key}\".");
    }
    $default_value = isset($schema['properties'][$key]['default']) ? $schema['properties'][$key]['default'] : $this->defaultByType($schema['properties'][$key]['type']);
    if (method_exists($this, $default_method)) {
      $default_value = $this->{$default_method}($default_value);

      // Cache this value if we are asked.
      if ($default_value instanceof RenderPatternsUncacheable) {
        $default_value = $default_value->value;
      }
      else {
        // We should cache this value.
        $this->overrides[$key] = $default_value;
      }
    }

    // Check for a method on the class.
    if ($has_dynamic_method) {
      return $this->{$get_method}($override_value, $default_value, $is_overridden);
    }

    return $is_overridden ? $override_value : $default_value;
  }

  /**
   * Return the default value based on declared type.
   */
  protected function defaultByType($type) {
    $type = is_array($type) ? reset($type) : $type;
    switch (strtolower($type)) {
      case 'null':
        return NULL;

      case 'object':
        return new \stdClass();

      case 'array':
        return [];

      case 'boolean':
        return FALSE;

      case 'float':
      case 'double':
        return floatval(NULL);

      case 'integer':
        return 0;

      case 'string':
        return '';
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function __set($key, $value) {
    $this->validateKeyValueBySchema($key, $value);
    $this->overrides[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($key) {
    return array_key_exists($key, $this->overrides);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($key) {
    unset($this->overrides[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (!function_exists('drupal_render')) {
      throw new \RuntimeException("Missing function drupal_render().");
    }
    $build = $this->build();

    return drupal_render($build);
  }

  /**
   * Return a SMACSS [cl]ass name based on $this->module.
   *
   * @param string|array $name
   *   If this is an array, each element will be converted to a class.
   * @param bool $isComponent
   *   If it's not a component (base__thing) then it's a style (base--style).
   *   This determines the character used to glue the $name to the module.
   *
   * @return string
   *   The classname
   *
   * @deprecated This will be removed in a future version.
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
   * @param array $element
   *   The element to be wrapped.
   * @param string $name
   *   The name of the element, used for ::cl().
   */
  protected function ajaxWrap(&$element, $name) {
    $category = $this->cl($name);
    $class = $category . '__ajax-content';
    $ajax = array(
      'content' => array(
        0 => array(
          '#prefix' => '<div class="' . $class . '">',
          '#suffix' => '</div>',
        ),
        '#role' => 'content',
        '#selector' => ".$class",
        '#class' => $class,
      ),
    );

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
   * @param string $form_id
   *   The form id.
   *
   * @return array
   *   The form build array.
   */
  protected function getForm($form_id) {
    $args = func_get_args();
    $stash = $_POST;
    $_POST = array();
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
      $this->cache['schema'] = [];
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
