                         Drupal Module: Render Patterns

   Author: Aaron Klump [1]sourcecode@intheloftstudios.com

Summary

   It's like tpl files for render arrays.

   When building a site, if you find yourself rewriting the same
   combination of render arrays, say a repeating combination of several
   theme functions, (I would say) you are doing it wrong. This module is
   inspired by component based design and handles these patterns in
   easy-to-use php classes, one per file, that are simply added to your
   module or theme, in a specified folder`. For me it has had the effect
   of taming the render array in my Drupal projects.

   You may also visit the [2]project page on Drupal.org.

Requirements

    1. A very basic understanding of php objects is needed to produce the
       code used by this module. Most Drupal site builders will already be
       familiar with this.

Installation

    1. Install as usual, see [3]http://drupal.org/node/70151 for further
       information.

Configuration

   There is nothing to configure aside from creating your classes as shown
   below. Advanced help will reveal more documentation if you enable it.

Suggested Use

   This probably should not be used as a one-to-one replacement for a
   render array based on a single theme function as this just adds a layer
   of abstraction and complexity. It would be more straitforward to just
   create a render array directly.

   And if you are not repeating the render array, you should also consider
   just creating the render array directly.

   However, where this module really takes over is when you have a pattern
   that combines multiple render arrays and is repeated. The following
   example tries to unveil why this module can be so helpful. One solution
   would be to create functions that take arguments and return render
   arrays, but I think this approach is cleaner and easier to maintain.

How you implement

<?php
$obj = render_patterns_get("ListOfThumbs");
$obj->images = array(
  'public://sun.jpg',
  'public://moon.jpg',
  'public://stars.jpg',
);
$render = $obj->build();

What you get in $render

Array
(
    [#theme] => item_list
    [#type] => ul
    [#items] => Array
        (
            [0] => <img src="http://localhost/sites/default/files/styles/thumb/p
ublic/sun.jpg" alt="" />
            [1] => <img src="http://localhost/sites/default/files/styles/thumb/p
ublic/moon.jpg" alt="" />
            [2] => <img src="http://localhost/sites/default/files/styles/thumb/p
ublic/stars.jpg" alt="" />
        )

    [#attributes] => Array
        (
            [class] => list-of-thumbs
        )

)

What you had to do to get there

    1. Enable this module.
    2. Create a render pattern by creating a file called
       THEME/render_patterns/ListOfThumbsRenderPattern.php the contents of
       which are:
<?php
/**
 * @file
 * Generates a render pattern called ListOfThumbsRenderPattern
 */

/**
 * Represents a ListOfThumbsRenderPattern object class.
 *
 * @brief Renders images in a thumbnail image style as a list.
 */
class ListOfThumbsRenderPattern extends RenderPatternsPattern {

  public function defaults() {
    return array(
      'images' => array(),
      'style' => 'thumb',
    );
  }

  public function build() {
    $items = array();
    foreach ($this->images as $uri) {
      $items[] = array(
        '#theme' => 'image_style',
        '#style_name' => 'thumb',
        '#path' => $uri,
      );
    }
    foreach ($items as &$item) {
      $item = drupal_render($item);
    }
    $build = array(
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#items' => $items,
      '#attributes' => array(
        'class' => 'list-of-thumbs',
      ),
    );

    return $build;
  }
}

One more point render().

   Notice that you can go directly to the rendered version by using the
   render() method. This is what you might want to do inside of a
   *.tpl.php file, where you actually need a string as the return value.
   The following could show the contents of list-of-thumbs.tpl.php.
<?php
$obj = render_patterns_get("ListOfThumbs");
$obj->images = array(
  'public://sun.jpg',
  'public://moon.jpg',
  'public://stars.jpg',
);
print $obj->render();

   For clarity the above is equivalent to doing the following:
<?php
$obj = render_patterns_get("ListOfThumbs");
$obj->images = array(
  'public://sun.jpg',
  'public://moon.jpg',
  'public://stars.jpg',
);
print drupal_render($obj->build());

Design Decisions/Rationale

   With heavy use of render arrays in writing complex themes, I found that
   I was repeating the same render array configurations throughtout
   several locations: preprocessors, tpls, and display suite layouts. This
   became a headache to keep in sync if such a pattern changed. I thought,
   I need something like a theme declaration that returns a renderable
   array not a string. This module is my answer implementing a write once,
   use often approach for these "render patterns".

Contact

     * In the Loft Studios
     * Aaron Klump - Developer
     * PO Box 29294 Bellingham, WA 98228-1294
     * skype: intheloftstudios
     * d.o: aklump
     * [4]http://www.InTheLoftStudios.com

References

   1. mailto:sourcecode@intheloftstudios.com
   2. http://www.drupal.org/project/render_patterns
   3. http://drupal.org/node/70151
   4. http://www.InTheLoftStudios.com/
