<!--
id: arrays
tags: ''
-->

# Problems With Array Overloading

Because of the internals of the `RenderPatternsPattern` class, and how the defaults are handled using magic settings/getters, you cannot push array elements onto array based keys as you might imagine.  The example below shows what this means and offers two solutions.

## The problem: This will not work

    $obj = \Drupal\my_theme\RenderPatterns\ListOfThumbs::get();
    $obj->images[] = 'public://do.jpg';
    $obj->images[] = 'public://re.jpg';

## Solution 1

    $obj = \Drupal\my_theme\RenderPatterns\ListOfThumbs::get();
    $obj->images = [
      'public://do.jpg',
      'public://re.jpg',
    ];

## Solution 2

    $obj = \Drupal\my_theme\RenderPatterns\ListOfThumbs::get();
    $images = [];
    $images[] = 'public://do.jpg';
    $images[] = 'public://re.jpg';
    $obj->images = $images;
