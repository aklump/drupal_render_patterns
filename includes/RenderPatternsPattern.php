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
abstract class RenderPatternsPattern implements RenderPatternsPatternInterface {

    use DataTrait;

    protected $vars = array();
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
        $default = !array_key_exists($key, $this->cache['defaults']) ? null : $this->cache['defaults'][$key];
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

    /**
     * Return a SMACSS [cl]ass name based on $this->module.
     *
     * @param string|array $name        If this is an array, each element will
     *                                  be converted to a class.
     * @param bool         $isComponent If it's not a component (base__thing)
     *                                  then it's a style (base--style).  This
     *                                  determines the character used to glue
     *                                  the $name to the module.
     *
     * @return string
     */
    protected function cl($name = '', $isComponent = true)
    {
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
    protected function ajaxWrap(&$element, $name)
    {
        $category = $this->cl($name);
        $class = $category . '__ajax-content';
        $ajax = array(
            'content' => array(
                0           => array(
                    '#prefix' => '<div class="' . $class . '">',
                    '#suffix' => '</div>',
                ),
                '#role'     => 'content',
                '#selector' => ".$class",
                '#class'    => $class,
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
     * @param $form_id
     *
     * @return array
     */
    protected function getForm($form_id)
    {
        $args = func_get_args();
        $stash = $_POST;
        $_POST = array();
        $form = call_user_func_array('drupal_get_form', $args);
        $_POST = $stash;

        return $form;
    }
}
