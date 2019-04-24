<?php

namespace Drupal\Tests\render_patterns\Unit;

use AKlump\DrupalTest\Drupal8\UnitTestCase;
use Drupal\data_api\Data;
use Drupal\render_patterns\Pattern;
use Drupal\render_patterns\Uncacheable;
use AKlump\PHPUnit\EasyMock;

/**
 * @group render_patterns
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PatternTest extends UnitTestCase {

  protected function getSchema() {
    return [
      'classToBeTested' => AlphaPattern::class,
      'classArgumentsMap' => [
        'dataApiData' => [new Data(), EasyMock::VALUE],
      ],
    ];
  }

  public function testAjaxWrapAddsCorrectMarkup() {
    $this->obj->module = 'blog';
    $build = $this->obj->build();

    $data = $build['#ajax_elements']['content'];
    $this->assertSame('blog__copy__ajax-content', $data['#class']);
    $this->assertSame('content', $data['#role']);
    $this->assertSame('.blog__copy__ajax-content', $data['#selector']);
    $this->assertSame('<div class="blog__copy__ajax-content">', $data[0]['#prefix']);
    $this->assertSame('</div>', $data[0]['#suffix']);

    $this->assertSame('<div class="blog__copy__ajax-content">', $build['#prefix']);
    $this->assertSame('</div>', $build['#suffix']);

    $this->assertSame('Hello World', $build[0]['#markup']);
  }

  public function testIssetAndUnsetWorksAsExpected() {
    $this->assertFalse(isset($this->obj->name));
    $this->assertSame('Bert', $this->obj->name);

    $this->obj->name = 'Melania';
    $this->assertTrue(isset($this->obj->name));
    $this->assertSame('Melania', $this->obj->name);

    unset($this->obj->name);
    $this->assertFalse(isset($this->obj->name));
    $this->assertSame('Bert', $this->obj->name);
  }

  public function testDynamicGetterReceivesProperArguments() {
    list($value, $default, $is_overridden) = $this->obj->color;
    $this->assertSame(NULL, $value);
    $this->assertSame('', $default);
    $this->assertSame(FALSE, $is_overridden);

    $this->obj->color = 'blue';
    list($value, $default, $is_overridden) = $this->obj->color;
    $this->assertSame('blue', $value);
    $this->assertSame('', $default);
    $this->assertSame(TRUE, $is_overridden);
  }

  /**
   * @expectedException \Drupal\render_patterns\PatternException
   */
  public function testInvalidEnumThrows() {
    $obj = new BravoPattern(new Data());
    $obj->size = 'fish';
  }

  /**
   * @expectedException \Drupal\render_patterns\PatternException
   */
  public function testLegacyDefaultsThrowsWhenBadTypeIsSet() {
    $obj = new LegacyPattern(new Data());
    $obj->breakfast = ['oatmeal'];
  }

  public function testSchemaPropertyIsRecognized() {
    $obj = new BravoPattern(new Data());
    $this->assertSame('md', $obj->size);
  }

  /**
   * @expectedException \Drupal\render_patterns\PatternException
   */
  public function testTryingToGetInvalidKeyThrows() {
    $this->obj->bogus = 'value';
  }

  public function testDefaultCallbackWorksAsExpectedWithCache() {
    $this->assertSame(1983, $this->obj->birth);
    $this->assertSame(1983, $this->obj->birth);
    $this->assertSame(1983, $this->obj->birth);
    $this->obj->birth = 1999;
    $this->assertSame(1999, $this->obj->birth);
    $this->assertSame(1999, $this->obj->birth);
  }

  public function testDefaultCallbackWorksAsExpectedNoCache() {
    $this->assertSame(11, $this->obj->counter);
    $this->assertSame(12, $this->obj->counter);
    $this->assertSame(13, $this->obj->counter);
    $this->obj->counter = 6;
    $this->assertSame(6, $this->obj->counter);
    $this->assertSame(6, $this->obj->counter);
  }

  public function testStaticDefaultValueCanBeOverridden() {
    $this->assertSame('Bert', $this->obj->name);
    $this->obj->name = 'Ernie';
    $this->assertSame('Ernie', $this->obj->name);
  }

  public function testLegacyDefaultsMethodIsConvertedToSchemaForTypeValidation() {
    $obj = new LegacyPattern(new Data());
    $obj->breakfast = 'oatmeal';
    $this->assertSame('oatmeal', $obj->breakfast);
  }

  public function testLegacyDefaultsMethodIsConvertedToSchemaForDefaults() {
    $obj = new LegacyPattern(new Data());
    $this->assertSame('pancakes', $obj->breakfast);
    $this->assertSame('fish', $obj->lunch);
    $this->assertSame('soup', $obj->dinner);
  }

  public function testClassCanSetPublicProperties() {
    $this->assertSame('Grasslands', $this->obj->title);
  }

  public function testDefaultArrayTypeReturnsEmptyArrayWhenNoOverride() {
    $this->assertSame([], $this->obj->list);
    $this->assertInternalType('object', $this->obj->thing);
    $this->assertSame(0, $this->obj->int);
    $this->assertSame(FALSE, $this->obj->bool);
    $this->assertSame(0.0, $this->obj->float);
    $this->assertSame('', $this->obj->string);
    $this->assertSame(NULL, $this->obj->null);
    $this->assertSame(0, $this->obj->compound);
  }

}

class LegacyPattern extends Pattern {

  public static function defaults() {
    return [
      'breakfast' => 'pancakes',
      'lunch' => 'fish',
      'dinner' => 'soup',
    ];
  }

  public function build(): array {
    return [];
  }

}

class BravoPattern extends Pattern {

  protected $schema = [
    'properties' => [
      'size' => [
        'type' => 'string',
        'enum' => ['sm', 'md', 'lg'],
        'default' => 'md',
      ],
    ],
  ];

  public function build(): array {
    return ['#markup' => "Size is: " . $this->size];
  }

}

class AlphaPattern extends Pattern {

  public $title = 'Grasslands';

  protected $properties = [
    'module' => [
      'type' => 'string',
    ],
    'name' => [
      'type' => 'string',
      'default' => 'Bert',
    ],
    'counter' => [
      'type' => 'integer',
      'default' => 10,
    ],
    'birth' => [
      'type' => 'integer',
      'default' => 1982,
    ],
    'color' => [
      'type' => 'string',
      'default' => '',
    ],

    // These are tests to make sure defaults are based on type.
    'list' => [
      'type' => 'array',
    ],
    'thing' => [
      'type' => 'object',
    ],
    'int' => [
      'type' => 'integer',
    ],
    'bool' => [
      'type' => 'boolean',
    ],
    'float' => [
      'type' => 'float',
    ],
    'string' => [
      'type' => 'string',
    ],
    'null' => [
      'type' => 'null',
    ],
    'compound' => [
      'type' => ['integer', 'string'],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = ['#markup' => 'Hello World'];
    $this->ajaxWrap($build, 'copy');

    return $build;
  }

  public function default__birth($default) {
    static $counter;
    if (empty($counter)) {
      $counter = $default;
    }

    return ++$counter;
  }

  public function default__counter($default) {
    static $counter;
    if (empty($counter)) {
      $counter = $default;
    }
    return Uncacheable::value(++$counter);
  }

  public function get__color($value, $default, $is_overridden) {
    return func_get_args();
  }

}
