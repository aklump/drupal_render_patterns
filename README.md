# Drupal Module: Render Patterns

**Author:** Aaron Klump  <sourcecode@intheloftstudios.com>

## Summary

The _Render Patterns_ module allows you to encapsulate Drupal render arrays as class objects, for repetitive use.  You expose only the dynamic elements of your render array as class properties, and the rest of the render array is hidden within the black box of the render pattern class.  This type of design makes sense if you need to reference the same render array in more than one place as it avoids errors caused by code duplication.  It comes from the [DRY principle](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself).

You may also visit the [project page](http://www.drupal.org/project/render_patterns) on Drupal.org.

## Requirements

1. A very basic understanding of php objects is needed to produce the code used by this module.  Most Drupal site builders will already be familiar with this.

## Installation

1. Install as usual, see [http://drupal.org/node/70151](http://drupal.org/node/70151) for further information.

## Configuration

There is nothing to configure aside from creating your classes as shown below.  Advanced help will reveal more documentation if you enable it.

## Suggested Use

This probably should not be used as a one-to-one replacement for a render array based on a single theme function as this just adds a layer of abstraction and complexity.  It would be more straitforward to just create a render array directly.  

And if you are not repeating the render array, you should also consider just creating the render array directly.

However, where this module really takes over is when you have a pattern that combines multiple render arrays and is repeated.  The following example tries to unveil why this module can be so helpful.  One solution would be to create functions that take arguments and return render arrays, but I think this approach is cleaner and easier to maintain.

## How you implement

    <?php
    $render_array = render_patterns_get("ListOfThumbs", [
      'images' => [
         'public://sun.jpg',
         'public://moon.jpg',
         'public://stars.jpg',
      ]
    ])->build();

## What you get in `$render_array`

    Array
    (
        [#theme] => item_list
        [#type] => ul
        [#items] => Array
            (
                [0] => <img src="http://localhost/sites/default/files/styles/thumb/public/sun.jpg" alt="" />
                [1] => <img src="http://localhost/sites/default/files/styles/thumb/public/moon.jpg" alt="" />
                [2] => <img src="http://localhost/sites/default/files/styles/thumb/public/stars.jpg" alt="" />
            )

        [#attributes] => Array
            (
                [class] => list-of-thumbs
            )

    )

## What you had to do to get there

1. Enable this module.
1. Create a render pattern by creating a file called `THEME/render_patterns/ListOfThumbs.php` the contents of which are:

        <?php
        
        use \Drupal\render_patterns\Pattern;
        
        /**
         * Represents a ListOfThumbs object class.
         * 
         * @brief Renders images in a thumbnail image style as a list.
         */
        class ListOfThumbs extends Pattern {
        
          protected $properties = [
            'images' => [
              'type' => 'array',
            ],
            'style' => [
              'type' => 'string',
              'default' => 'thumb',
            ]
          ]

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

## One more point `render()`.

Notice that you can go directly to the rendered version by using the `render()` method. This is what you might want to do inside of a *.tpl.php file, where you actually need a string as the return value.  The following could show the contents of `list-of-thumbs.tpl.php`.

    <?php
    print render_patterns_get("ListOfThumbs", [
      'images' => [
         'public://sun.jpg',
         'public://moon.jpg',
         'public://stars.jpg',
      ]
    ])->render();

For clarity the above is equivalent to doing the following:

    <?php
    $obj = render_patterns_get("ListOfThumbs");
    $obj->images = array(
      'public://sun.jpg',
      'public://moon.jpg',
      'public://stars.jpg',
    );
    print drupal_render($obj->build());

## Design Decisions/Rationale

With heavy use of render arrays in writing complex themes, I found that I was repeating the same render array configurations throughtout several locations: preprocessors, tpls.  This became a headache to keep in sync if such a pattern changed.  I thought, I need something like a theme declaration that returns a renderable array not a string.  This module is my answer implementing a write once, use often approach for these "render patterns".

## Contact

* **In the Loft Studios**
* Aaron Klump - Developer
* PO Box 29294 Bellingham, WA 98228-1294
* _skype_: intheloftstudios
* _d.o_: aklump
* <http://www.InTheLoftStudios.com>
