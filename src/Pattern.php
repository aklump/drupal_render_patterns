<?php

namespace Drupal\render_patterns;

use Drupal\Component\Utility\DefaultValue;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

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
abstract class Pattern implements PatternInterface {

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
  protected function validateKeyValueBySchema(string $key, $value): void {
    $schema = $this->getSchema();

    // Verify $key is allowed to be set.
    $allowed_properties = array_keys($schema['properties'] ?? []);
    if (!in_array($key, $allowed_properties)) {
      throw new PatternException(get_class($this), "\"$key\" is not an allowed property");
    }

    $data = (object) [
      $key => $value,
    ];

    // When validating a single key we have no concept of the dataset so, we
    // have to ignore the required part of the schema.
    unset($schema['required']);

    try {
      $this->validateFullyQualifiedNamedConstraints($data, $schema);
      $this->validator = $this->validator ?? new Validator();
      $this->validator->validate($data, $schema, Constraint::CHECK_MODE_EXCEPTIONS);
    }
    catch (\Exception $exception) {
      throw new PatternException(get_class($this), sprintf("Invalid type \"%s\" for property \"%s\" >>> %s", gettype($value), $key, $exception->getMessage()), $exception);
    }
  }

  /**
   * Validate objects where the schema asks for a FQN.
   *
   * @param object $data
   *   The data to check against the schema.
   * @param array $schema
   *   The JSON schema.
   *
   * @throws \Drupal\render_patterns\PatternException
   *   When objects do not validate according to their FQN.
   */
  private function validateFullyQualifiedNamedConstraints(object $data, array &$schema) {
    foreach ($data as $key => $datum) {
      if (!is_object($datum)) {
        continue;
      }
      $schema_type =& $schema['properties'][$key]['type'];
      if ($schema_type === 'object') {
        continue;
      }
      $valid_types = is_string($schema_type) ? [$schema_type] : $schema_type;
      $is_valid = FALSE;
      foreach ($valid_types as $fqcn) {
        $is_valid = $fqcn === 'object' || $datum instanceof $fqcn;
        if ($is_valid) {
          break;
        }
      }

      if (!$is_valid) {
        $actual_type = get_class($datum);
        if (is_array($schema_type)) {
          throw new PatternException(get_class($this), sprintf('Property "%s", (%s), is not any instance of: %s.', $key, $actual_type, implode(',', $schema_type)));
        }
        else {
          throw new PatternException(get_class($this), sprintf('Property "%s", ($s), is not an instance of %s.', $key, $actual_type, $schema_type));
        }
      }
    }

    // Replace FQN with native 'object' so JSON validator will process
    // correctly since it doesn't handle FQN objects.
    $replace = function ($type) {
      return strstr($type, '\\') ? 'object' : $type;
    };
    foreach ($data as $key => $datum) {
      $schema_type =& $schema['properties'][$key]['type'];
      if (is_array($schema_type)) {
        $schema_type = array_map($replace, $schema_type);
      }
      else {
        $schema_type = $replace($schema_type);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __get($key) {
    $get_method = "get__$key";
    $has_dynamic_method = method_exists($this, $get_method);
    $is_overridden = array_key_exists($key, $this->overrides);
    $override_value = $this->overrides[$key] ?? NULL;
    if (!$has_dynamic_method && $is_overridden) {
      return $override_value;
    }

    // Determine the default because we don't have an overridden value .
    $default_method = "default__{$key}";
    $schema = $this->getSchema();
    if (!isset($schema['properties'][$key]['type'])) {
      throw new PatternException(get_class($this), "Incomplete schema for \"{$key}\".");
    }

    $has_default_value_method = method_exists($this, $default_method);
    if (array_key_exists('default', $schema['properties'][$key])) {
      $default_value = $schema['properties'][$key]['default'];
    }
    else {
      try {
        $default_value = $this->defaultByType($key, $schema['properties'][$key]['type']);
      }
      catch (\Exception $exception) {
        if (!$has_default_value_method) {
          throw $exception;
        }
        $default_value = NULL;
      }
    }

    if ($has_default_value_method) {
      $default_value = $this->{$default_method}($default_value);

      // Cache this value if we are asked.
      if ($default_value instanceof Uncacheable) {
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
  private function defaultByType($key, $type) {
    try {
      if (is_array($type)) {
        $type = array_first($type);
      }

      return DefaultValue::get($type);
    }
    catch (\Exception $exception) {
      $called_class = get_called_class();
      $how_to_fix = sprintf("You must provide a runtime value, a default value as %s::\$properties['%s']['default'], or implement %s::default__%s().", $called_class, $key, $called_class, $key);
      throw new PatternException(get_class($this), $exception->getMessage() . ' ' . $how_to_fix);
    }
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
  public function render(): string {

    // TODO Explore the implications of render contexts with this.  May want to deprectate this method. 2019-03-16T10:11, aklump.
    return \Drupal::service('renderer')->renderRoot($this->build());
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
    if (!$this->module) {
      throw new PatternException(get_class($this), "You must set \"module\" to use ::cl().");
    }
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
  protected function ajaxWrap(array &$element, string $name): void {
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
   * @param string $form_id
   *   The form id.
   *
   * @return array
   *   The form build array.
   */
  protected function getForm(string $form_id): array {
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
  protected function getSchema(): array {
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
