# Drupal Module: Render Patterns

**Author:** Aaron Klump  <sourcecode@intheloftstudios.com>

## Summary

The _Render Patterns_ module allows you to encapsulate Drupal render arrays as class objects, for repetitive use.  You expose only the dynamic elements of your render array as class properties, and the rest of the render array is hidden within the black box of the render pattern class.  This type of design makes sense if you need to reference the same render array in more than one place as it avoids errors caused by code duplication.  It comes from the [DRY principle](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself).

You may also visit the [project page](http://www.drupal.org/project/render_patterns) on Drupal.org.

## Installation

1. Download this module to _web/modules/custom/render_patterns_.
1. Add the following to the application's _composer.json_ above web root.

    ```json
    {
      "repositories": [
        {
          "type": "path",
          "url": "web/modules/custom/render_patterns"
        }
      ]
    }
    ```

1. Now run `composer require drupal/render-patterns`
1. Enable this module.

## Usage

{% include('_properties.md') %}

## Contact

* **In the Loft Studios**
* Aaron Klump - Developer
* PO Box 29294 Bellingham, WA 98228-1294
* _skype_: intheloftstudios
* _d.o_: aklump
* <http://www.InTheLoftStudios.com>
