<?php

namespace Drupal\render_patterns;

use Drupal\Component\Utility\DefaultValue;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
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
   * Holds overridden values.
   *
   * @var array
   */
  private $values = [];

  /**
   * @var array
   */
  private $schema = [];

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
    if (empty($this->schema)) {
      $this->schema = [
        'properties' => $this->getProperties(),
      ];

      // If the schema does not define the default value, then we will smart
      // guess it based on the property type.
      foreach ($this->schema['properties'] as $property => &$definition) {

        // Handle the "default" key.
        if (!array_key_exists('default', $definition)) {
          if (empty($definition['type'])) {
            throw new PatternException($this, 'Missing "type" for the property "$property_name".');
          }
          try {
            if (is_array($definition['type'])) {
              $definition['type'] = array_first($definition['type']);
            }
            $definition['default'] = DefaultValue::get($definition['type']);
          }
          catch (\Exception $exception) {
            $definition['default'] = NULL;
          }
        }

        // Handle the "alter" key, which is a callback to alter __get().

        // If we have a dynamic method "get__foo" then call it.  This may be
        // deprecated in the future for some other pattern.
        $alter_method = "get__{$property}";
        if (method_exists($this, $alter_method)) {
          if (isset($definition['alter'])) {
            throw new PatternException(static::class, sprintf('You have defined "alter" for the property "%s" AND you have defined the method "%s()";  you must pick one or the other.', $property, $alter_method));
          }
          $definition['alter'] = [$this, $alter_method];
        }
      }
    }

    return $this->schema;
  }

  /**
   * Retrieve the property definitions.
   *
   * @return array
   *   The property definitions.
   */
  abstract protected function getProperties(): array;

  /**
   * Magic getter for overloaded properties.
   *
   * @param $key
   *
   * @throws \Drupal\render_patterns\PatternException
   */
  public function __get($property) {
    $properties = $this->getSchema()['properties'];

    // If the value is NULL then we grab the default from the schema.
    if (($this->values[$property] ?? NULL) === NULL) {
      $this->values[$property] = $properties[$property]['default'];
      if (is_callable($this->values[$property])) {
        // Note: `$this` is available inside this callback.
        $this->values[$property] = $this->values[$property]($property);
      }
    }

    if (is_callable($properties[$property]['alter'] ?? FALSE)) {
      $is_overridden = $this->values[$property] !== $properties[$property]['default'];
      $this->values[$property] = $properties[$property]['alter'](
        $this->values[$property],
        $properties[$property]['default'],
        $is_overridden
      );
    }

    return $this->values[$property];
  }

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
      $json_validator = new Validator();
      $json_validator->validate($data, $schema, Constraint::CHECK_MODE_EXCEPTIONS);
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

  /**
   * {@inheritdoc}
   */
  public function __set($key, $value) {
    $this->validateKeyValueBySchema($key, $value);
    $this->values[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($key) {
    return ($this->values[$key] ?? NULL) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($key) {
    unset($this->values[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public static function get(array $values = []): PatternInterface {
    try {
      if (in_array(ContainerInjectionInterface::class, class_implements(static::class))) {
        $instance = static::create(\Drupal::getContainer());
      }
      else {
        $instance = new static();
      }
      foreach ($values as $key => $value) {

        // We do this so that the pattern has a chance to provide the
        // default values to us.  If we didn't do the magic getter, then we
        // could get an exception when setting $key if NULL is not allowed.
        $value = $value ?? $instance->{$key};
        $instance->{$key} = $value;
      }

      return $instance;
    }
    catch (PatternException $exception) {
      watchdog_exception('render_patterns', $exception);
      if (\Drupal::currentUser()
        ->hasPermission('access administration pages')) {
        \Drupal::messenger()->addError($exception->getMessage());
      }
      throw $exception;
    }
    catch (\Exception $exception) {
      watchdog_exception('render_patterns', $exception);
      throw $exception;
    }
  }

}
