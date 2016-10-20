<?php
/**
 * @file
 * Defines the Pattern class.
 */

/**
 * Represents a Pattern object class.
 *
 * @brief An abstract base for all Pattern classes.
 */
abstract class RenderPatternsPattern implements RenderPatternsPatternInterface
{
    protected $vars;

    public function __get($key)
    {
        $d = $this->defaults();
        $default = isset($d[$key]) ? $d[$key] : null;

        return isset($this->vars[$key]) ? $this->vars[$key] : $default;
    }

    public function __set($key, $value)
    {
        $this->vars[$key] = $value;
    }

    public function __isset($key)
    {
        return array_key_exists($key, $this->defaults());
    }

    public function render()
    {
        if (!function_exists('drupal_render')) {
            throw new \RuntimeException("Missing function drupal_render().");
        }
        $build = $this->build();

        return drupal_render($build);
    }
}
