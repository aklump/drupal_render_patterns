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
 * You may use the following API methods to influence property values; see
 *   documentation for more info.
 * - protected function default__{property_name}()
 * - protected function get__{property_name}{))
 *
 * @link https://json-schema.org/latest/json-schema-validation.html
 */
abstract class Pattern implements PatternInterface {

  /**
   * @var array
   */
  private static $schema = [];

  /**
   * The hardcoded properties section of the JSON schema.
   *
   * @var array
   *
   * @deprecated Use ::getProperties() instead.
   */
  protected $properties;

  /**
   * Holds overridden values.
   *
   * @var array
   */
  private $overrides = [];

  /**
   * An instance of the JSON schema validator.
   *
   * @var \JsonSchema\Validator
   */
  private $validator;

  /**
   * Verify the $value of $key fits in the schema definition.
   *
   * @param string $key
   *   The property name as defined in ::getProperties().
   * @param mixed $value
   *   The value to validate for the property $key.
   *
   * @throws \Drupal\render_patterns\PatternException
   *   If $key does not appear in the schema properties.
   *   If $value does not pass the schema validation.
   */
  private function validateKeyValueBySchema(string $key, $value): void {
    $schema = $this->getSchema();

    // When validating a single key we have no concept of the dataset so, we
    // have to remove the required part of the schema, so it doesn't throw.
    unset($schema['required']);

    // Verify $key is allowed to be set.
    $allowed_properties = array_keys($schema['properties'] ?? []);
    if (!in_array($key, $allowed_properties)) {
      throw new PatternException(get_class($this), "\"$key\" is not an allowed property");
    }

    try {
      $data = (object) [$key => $value];
      $this->validateFullyQualifiedNamedConstraints($data, $schema);
      $this->validator = $this->validator ?? new Validator();
      $this->validator->validate($data, $schema, Constraint::CHECK_MODE_EXCEPTIONS);
    }
    catch (\Exception $exception) {
      $message = sprintf("Invalid type \"%s\" for property \"%s\" >>> %s", gettype($value), $key, $exception->getMessage());
      throw new PatternException(get_class($this), $message, $exception);
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

  private function __gett($key) {
  }

  /**
   * {@inheritdoc}
   */
  private function __get($key) {
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
        $default_value = $this->getPropertyDefaultValue($key, $schema['properties'][$key]['type']);
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
  private function getPropertyDefaultValue($key, $type) {
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
  private function __set($key, $value) {
    $this->validateKeyValueBySchema($key, $value);
    $this->overrides[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  private function __isset($key) {
    return ($this->overrides[$key] ?? NULL) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  private function __unset($key) {
    unset($this->overrides[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): string {

    // TODO Explore the implications of render contexts with this.  May want to deprectate this method. 2019-03-16T10:11, aklump.
    $build = $this->build();

    return \Drupal::service('renderer')->renderRoot($build);
  }

  /**
   * {@inheritdoc}
   */
  protected function getProperties() {

    // To support the deprecated pattern, which will eventually be removed.
    return $this->properties || [];
  }

  /**
   * Return the schema definition.
   *
   * To alter the schema you should override this method in your final pattern
   * class.
   *
   * @return array
   *   The schema for this render pattern.
   */
  public function getSchema(): array {
    if (empty(self::$schema)) {
      self::$schema = [
        'properties' => $this->getProperties(),
      ];
    }

    return self::$schema;
  }

}
