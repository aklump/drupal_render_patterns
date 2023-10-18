# Drupal Module: Render Patterns

**Author:** Aaron Klump  <sourcecode@intheloftstudios.com>

## Summary

The _Render Patterns_ module allows you to encapsulate Drupal render arrays as PHP classes, for repeat use.  You expose only the dynamic elements of your render array as class properties, and the rest of the render array is hidden within the black box of the render pattern class.  This type of design makes sense if you need to reference the same render array in more than one place as it avoids errors caused by code duplication.  It comes from the [DRY principle](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself).

You may also visit the [project page](http://www.drupal.org/project/render_patterns) on Drupal.org.

## Installation

1. Add the following to the application's _composer.json_ above web root.

    ```json
    {
      "repositories": [
        {
          "type": "github",
          "url": "https://github.com/aklump/drupal_render_patterns"
        }
      ]
    }
    ```

3. Now run `composer require aklump_drupal/render_patterns`
4. Enable this module.
5. Begin creating one or more render patterns in _{active theme}/src/RenderPatterns/_.  (You may also provide classes in a module by adjusting the namespace to the module.)
6. Use namespace `\Drupal\my_theme\src\RenderPatterns` for the classes.

## Usage

{% include('_properties.md') %}

## Contact

* **In the Loft Studios**
* Aaron Klump - Developer
* PO Box 29294 Bellingham, WA 98228-1294
* _skype_: intheloftstudios
* _d.o_: aklump
* <http://www.InTheLoftStudios.com>
