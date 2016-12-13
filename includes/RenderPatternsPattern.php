<?php
/**
 * @file
 * Defines the Pattern class.
 */
use Drupal\data_api\Data;
use Drupal\data_api\DataTrait;

/**
 * Represents a Pattern object class.
 *
 * @brief An abstract base for all Pattern classes.
 */
abstract class RenderPatternsPattern implements RenderPatternsPatternInterface
{
    use DataTrait;

    protected $vars;
    protected $cache;

    /**
     * RenderPatternsPattern constructor.
     */
    public function __construct(Data $dataApiData)
    {
        $this->setDataApiData($dataApiData);
        $this->cache['defaults'] = static::defaults();
    }

    public function __get($key)
    {
        $default = !array_key_exists($key, $this->cache['defaults']) ?: $this->cache['defaults'][$key];
        $value = !($exists = array_key_exists($key, $this->vars)) ? $default : $this->vars[$key];
        $hook = "get__$key";
        if (!method_exists($this, $hook)) {
            return $value;
        }

        return $this->{$hook}($value, $default, $exists);
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
