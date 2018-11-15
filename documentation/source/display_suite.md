# Display Suite Integration

If you model your render pattern after a display suite layout and then implement the pattern in the layout tpl as seen below, you have the ultimate flexibility as you can programatically use the display suite layout very easily.

This example implements the display suite layout in the theme and we use these files for the complete solution.

    THEME/ds_layouts/example/
    THEME/ds_layouts/example/example.inc
    THEME/ds_layouts/example/example.tpl.php
    THEME/render_patterns/
    THEME/render_patterns/ExampleBasedOnDsLayoutRenderPattern.php

## `example.inc`

    <?php
    function ds_example() {
      return array(
        'label' => t('Example'),
        'regions' => array(
          'content' => t('Content'),
          'footer' => t('Footer'),
        ),
      );
    }

## `example.tpl.inc`

    <?php
    /**
     * @file
     * Template for the example Display Suite layout.
     *
     * Variables:
     * - rendered_by_ds bool
     * - layout_attributes string
     * - layout_wrapper string
     * - content renderable
     * - content_classes string
     * - content_wrapper string
     * - footer renderable
     * - footer_classes string
     * - footer_wrapper string
     */
    $pattern = render_patterns_get('ExampleBasedOnDsLayout');
    $pattern->tag = 'div';
    
    // Now inject the variables from display suite.
    $pattern->content         = $content;
    $pattern->content_classes = $content_classes;
    $pattern->content_wrapper = $content_wrapper;
    $pattern->footer         = $footer;
    $pattern->footer_classes = $footer_classes;
    $pattern->footer_wrapper = $footer_wrapper;

    print $hp->render();

## `ExampleBasedOnDsLayoutRenderPattern.php`
    <?php
    /**
     * @file
     * Generates a render pattern called ExampleBasedOnDsLayoutRenderPattern
     */

    /**
     * Represents a ExampleBasedOnDsLayoutRenderPattern object class.
     * 
     * @brief Shows how to integrate with display suite; notice that we are
     * extending a different class, that is RenderPatternsDSPattern and not just
     * RenderPatternsPattern.
     *
     * @see ds_example().
     */
    class ExampleBasedOnDsLayoutRenderPattern extends RenderPatternsDSPattern {

      // Instead of implementing the defaults() method we merely map this to the
      // ds_layout and let that module provide the defaults for us.  If you need
      // to provide additional defaults beyond the ds layout, then do like has
      // be done here with the defaults method(), calling parent::defaults().
      protected $ds_layout = 'example';

      // parent::defaults() insures that all keys from the layout are set so
      // you must do the following when setting non-layout defaults.
      public function defaults() {
        return array(
          'tag' => 'h2',
        ) + parent::defaults();
      }

      // The build method is the same.
      public function build() {
        $build[] = array(
          '#theme' => 'html_tag', 
          '#tag' => $this->tag, 
          '#value' => t('Content'),
        );
        $build[] = array(
          '#theme' => 'region',
          '#value' => drupal_render($this->content),
        );
        $build[] = array(
          '#theme' => 'html_tag', 
          '#tag' => $this->tag, 
          '#value' => t('Footer'),
        );
        $build[] = array(
          '#theme' => 'region',
          '#value' => drupal_render($this->footer),
        );

        return $build;  
      }
    }
