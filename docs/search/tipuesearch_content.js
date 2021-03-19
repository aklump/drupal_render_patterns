var tipuesearch = {"pages":[{"title":"Changelog","text":"  [8.x-2.0] - 2020-02-14  Changed   Switched to semantic versioning.   Removed   Dependency on drupal:data_api.  See documentation update-v2.md on how to handle this breaking change.   8.x-1.1-rc1 2019-03-15T16:16, aklump   You must declare all properties as protected or in the schema. Change all your patterns to extend \\Drupal\\render_patterns\\Pattern instead of RenderPatternsPattern. You must add the PHP 7 typehints for: build and render per \\Drupal\\render_patterns\\PatternInterface. It is no longer recommended to suffix your classes with RenderPattern. You may now pass an array of values as the second argument to render_patterns_get to set those properties on instantiation. public static defaults() has been deprecated.  You should migrate to class::$properties. Added JSONSchema validation via Pattern::$properties.  Use it to define the property schema per JSON Schema format for automatic validation.   7.x-1.1-rc5   BREAKING CHANGE: defaults() should now be a public static function.  ","tags":"","url":"CHANGELOG.html"},{"title":"Drupal Module: Render Patterns","text":"  Author: Aaron Klump  &#x73;&#111;&#117;&#x72;&#x63;&#101;c&#x6f;&#100;&#101;&#x40;&#x69;&#110;&#116;&#x68;&#x65;&#108;&#x6f;&#x66;&#116;&#115;&#x74;&#x75;&#100;i&#x6f;&#115;&#46;&#x63;&#x6f;&#109;  Summary  The Render Patterns module allows you to encapsulate Drupal render arrays as class objects, for repetitive use.  You expose only the dynamic elements of your render array as class properties, and the rest of the render array is hidden within the black box of the render pattern class.  This type of design makes sense if you need to reference the same render array in more than one place as it avoids errors caused by code duplication.  It comes from the DRY principle.  You may also visit the project page on Drupal.org.  Requirements   A very basic understanding of php objects is needed to produce the code used by this module.  Most Drupal site builders will already be familiar with this.   Installation   Download this module to web\/modules\/custom\/render_patterns. Add the following to the application's composer.json above web root.  {   \"repositories\": [     {       \"type\": \"path\",       \"url\": \"web\/modules\/custom\/render_patterns\"     }   ] }  Now run composer require drupal\/render-patterns Enable this module.   Configuration  There is nothing to configure aside from creating your classes as shown below.  Advanced help will reveal more documentation if you enable it.  Suggested Use  This probably should not be used as a one-to-one replacement for a render array based on a single theme function as this just adds a layer of abstraction and complexity.  It would be more straitforward to just create a render array directly.  And if you are not repeating the render array, you should also consider just creating the render array directly.  However, where this module really takes over is when you have a pattern that combines multiple render arrays and is repeated.  The following example tries to unveil why this module can be so helpful.  One solution would be to create functions that take arguments and return render arrays, but I think this approach is cleaner and easier to maintain.  How you implement  &lt;?php $render_array = render_patterns_get(\"ListOfThumbs\", [   'images' =&gt; [      'public:\/\/sun.jpg',      'public:\/\/moon.jpg',      'public:\/\/stars.jpg',   ] ])-&gt;build();   What you get in $render_array  Array (     [#theme] =&gt; item_list     [#type] =&gt; ul     [#items] =&gt; Array         (             [0] =&gt; &lt;img src=\"http:\/\/localhost\/sites\/default\/files\/styles\/thumb\/public\/sun.jpg\" alt=\"\" \/&gt;             [1] =&gt; &lt;img src=\"http:\/\/localhost\/sites\/default\/files\/styles\/thumb\/public\/moon.jpg\" alt=\"\" \/&gt;             [2] =&gt; &lt;img src=\"http:\/\/localhost\/sites\/default\/files\/styles\/thumb\/public\/stars.jpg\" alt=\"\" \/&gt;         )      [#attributes] =&gt; Array         (             [class] =&gt; list-of-thumbs         )  )   What you had to do to get there   Enable this module. Create a render pattern by creating a file called THEME\/render_patterns\/ListOfThumbs.php the contents of which are:  &lt;?php  use \\Drupal\\render_patterns\\Pattern;  \/**  * Represents a ListOfThumbs object class.  *   * @brief Renders images in a thumbnail image style as a list.  *\/ class ListOfThumbs extends Pattern {    protected $properties = [     'images' =&gt; [       'type' =&gt; 'array',     ],     'style' =&gt; [       'type' =&gt; 'string',       'default' =&gt; 'thumb',     ]   ]    public function build() {     $items = array();     foreach ($this-&gt;images as $uri) {       $items[] = array(         '#theme' =&gt; 'image_style',         '#style_name' =&gt; 'thumb',         '#path' =&gt; $uri,       );     }     foreach ($items as &amp;$item) {       $item = drupal_render($item);     }     $build = array(       '#theme' =&gt; 'item_list',       '#type' =&gt; 'ul',       '#items' =&gt; $items,       '#attributes' =&gt; array(         'class' =&gt; 'list-of-thumbs',       ),     );      return $build;   } }    One more point render().  Notice that you can go directly to the rendered version by using the render() method. This is what you might want to do inside of a *.tpl.php file, where you actually need a string as the return value.  The following could show the contents of list-of-thumbs.tpl.php.  &lt;?php print render_patterns_get(\"ListOfThumbs\", [   'images' =&gt; [      'public:\/\/sun.jpg',      'public:\/\/moon.jpg',      'public:\/\/stars.jpg',   ] ])-&gt;render();   For clarity the above is equivalent to doing the following:  &lt;?php $obj = render_patterns_get(\"ListOfThumbs\"); $obj-&gt;images = array(   'public:\/\/sun.jpg',   'public:\/\/moon.jpg',   'public:\/\/stars.jpg', ); print drupal_render($obj-&gt;build());   Design Decisions\/Rationale  With heavy use of render arrays in writing complex themes, I found that I was repeating the same render array configurations throughtout several locations: preprocessors, tpls.  This became a headache to keep in sync if such a pattern changed.  I thought, I need something like a theme declaration that returns a renderable array not a string.  This module is my answer implementing a write once, use often approach for these \"render patterns\".  Contact   In the Loft Studios Aaron Klump - Developer PO Box 29294 Bellingham, WA 98228-1294 skype: intheloftstudios d.o: aklump http:\/\/www.InTheLoftStudios.com  ","tags":"","url":"README.html"},{"title":"Roadmap","text":"   Determine if patterns should be made into plugins? Should invalidate \\Drupal::cache('bootstrap')->get('render_patterns_list') when active theme changes.  ","tags":"","url":"ROADMAP.html"},{"title":"Using arrays when implementing a pattern","text":"  Because of the internals of the RenderPatternsPattern class, and how the defaults are handled using magic settings\/getters, you cannot push array elements onto array based keys as you might imagine.  The example below shows what this means and offers two solutions.  The problem: This will not work  $obj = render_patterns_get(\"ListOfThumbs\"); $obj-&gt;images[] = 'public:\/\/do.jpg'; $obj-&gt;images[] = 'public:\/\/re.jpg';   Solution 1  $obj = render_patterns_get(\"ListOfThumbs\"); $obj-&gt;images = array(   'public:\/\/do.jpg',   'public:\/\/re.jpg', );   Solution 2  $obj = render_patterns_get(\"ListOfThumbs\"); $images = array(); $images[] = 'public:\/\/do.jpg'; $images[] = 'public:\/\/re.jpg'; $obj-&gt;images = $images;  ","tags":"","url":"arrays.html"},{"title":"Patterns and Dependency Injection","text":"  Patterns that need services from the service container should implement the create method and a custom __construct.    \/**    * {@inheritdoc}    *\/   public static function create(ContainerInterface $container) {     return new static(       $container-&gt;get('data_api')     );   }  ","tags":"","url":"dependency-injection.html"},{"title":"Object Properties","text":"  A render pattern encapsulates the render array into a class and then exposes only those properties that may change across instances.  To set a property you have two options: set the value when you instantiate the pattern, or set it on the pattern directly.  In these examples I have defined a render pattern as a PHP class Drupal\\render_pattern\\Pattern\\Headline located in my default theme's folder as {theme dir}\/render&#95;patterns\/Headline.php.  Set the Property on Instantiation  $pattern = render_patterns_get('Headline', [   'title' =&gt; 'Hello World', ]);   Set the Property Directly  $pattern = render_patterns_get('Headline') $pattern-&gt;title = 'Hello World';   Using isset and unset  These work a little different, but logically.  isset($pattern-&gt;title) will return true if the title has been overridden.  It will return false, even if the default value has a value.  So it's really answering the question, \"Does title have a value other than it's default?\"  unset($pattern-&gt;title) will remove the override value and put it back in the default state.  Incidentally this will cause a default__* method, whose return value was previously cached, to be called again.  Property Validation  When you try to set a property value, it will be validated against the schema.  If the property is not allowed, or it's value is not valid then an error situation occurs.  How this manifests depends on when you're setting the property.  On instantion, as in the first example, the error is handled by drupal error messages; as well as the object returned is a markup render array with the error message.  So if you render the object, you will also see the error message.  When setting directly, i.e., $pattern-&gt;title, an exception is thrown.  Default Values  Default values will only be returned for a property if the property has not been set.  Once set, the default mechanisms as described below do not apply.  Default values, whether static or dynamic, are not validated per the schema, so be careful.  Static Default Values  Set your default values in pattern::$properties, using the key default, something like this:  protected $properties = [   'title' =&gt; [     'type' =&gt; 'string',     'default' =&gt; 'Title Goes Here',   ], ];         Dynamic Default Values  If you need to provide non-static values for a property, use a method that follows the naming convention of default_{property}, e.g. default__title.  This method will only be called if a value for the property has not been overridden.  It will be called once or always dependent upon how you return your value.  If the method returns the same value every time, it is cacheable.  Just return the default value like this.  The method will only be called the first time the property is accessed.    protected function default__tag() {     return 'h3';   }   If the method must do runtime calculation and may return a different default value each time, then it is not cacheable.  You must wrap the return value in Uncacheable::value() as seen in the example below.  This is an obvious use case because you want $pattern-&gt;now to always return the current time.    protected function default__now() {     return Uncacheable::value(time());   }           Get the Value  Getting the value goes through a series of steps.  Refer to this flowchart for more info:    get__* methods  These are expensive so only use if needed.  Try to use default__* first, if possible.  You should only use a get__* method if you need to process the value every time it is retrieved.  The return value is never cached.  This may be removed in a future version pending performance impact analysis. ","tags":"","url":"properties.html"},{"title":"Search Results","text":" ","tags":"","url":"search--results.html"},{"title":"Update your Render Patterns","text":"  You patterns will be broken when you update to 2.x, here's what you need to do.   Search for any overridden constructors...  public function __construct(   Data $data,   BlockRepositoryInterface $block_repository, ) {   $this-&gt;blockRepository = $block_repository;   parent::__construct($data); }  public static function create(ContainerInterface $container) {   return new static(     $container-&gt;get('data_api'),     $container-&gt;get('block.repository'),   ); }  ... and remove the references to \\AKlump\\Data\\Data; so the above becomes the following...  public function __construct(   BlockRepositoryInterface $block_repository,   EntityTypeManagerInterface $entity_type_manager ) {   $this-&gt;blockRepository = $block_repository;   parent::__construct(); }  public static function create(ContainerInterface $container) {   return new static(     $container-&gt;get('block.repository'),   ); }      To maintain compatibility  You can create a interim class like the following and update all your render patterns to extend it, rather than \\Drupal\\render_patterns\\Pattern.      &lt;?php      namespace Drupal\\my_module;      use AKlump\\Data\\DataInterface;     use Drupal\\data_api\\DataTrait;     use Drupal\\render_patterns\\Pattern;     use Symfony\\Component\\DependencyInjection\\ContainerInterface;      \/**      * An example showing how to maintain backwards compatibility.      *\/     abstract class PatternWithData extends Pattern {        use DataTrait;        \/**        * RenderPatternsPattern constructor.        *\/       public function __construct(DataInterface $dataApiData) {         $this-&gt;setDataApiData($dataApiData);         parent::__construct();       }        \/**        * {@inheritdoc}        *\/       public static function create(ContainerInterface $container) {         return new static(           $container-&gt;get('data_api')         );       }      }  ","tags":"","url":"update-v2.html"},{"title":"Property Validation","text":"  Validation is based on JSON Schema.  However we're not using JSON at all, but rather the pattern::$properties property on your class.  The API structured however follows JSON schema and you will build an array following those guidelines.  In most cases, $properties is the only key of the schema specification you will need to use.  However if you find some reason to need an entire schema, then set pattern::$schema instead and do not set pattern::$properties.  The required keys is ignored when validating a single key.    protected $properties = [     'title' =&gt; [       'type' =&gt; 'string',       'default' =&gt; 'Title Goes Here',     ],     'tag' =&gt; [       'type' =&gt; 'string',       'enum' =&gt; [         'h1',         'h2',         'h3',         'h4',         'h5',         'h6',       ],       'default' =&gt; 'h1',     ],   ];   description not comments  Instead of commenting your code, add a description key to make a property note or descriptio.  Wrong:  protected $properties = [   \/\/ Leave this null if the first batch is already loaded. For autoclick loading, set this to an integer total number to load.  The caller should most likely be checking $_GET for this value!!!   'load' =&gt; [     'type' =&gt; ['null', 'integer'],     'default' =&gt; NULL,   ], ];   Right:  protected $properties = [   'load' =&gt; [     'description' =&gt; 'Leave this null if the first batch is already loaded. For autoclick loading, set this to an integer total number to load.  The caller should most likely be checking $_GET for this value!!!',     'type' =&gt; ['null', 'integer'],     'default' =&gt; NULL,   ], ];  ","tags":"","url":"validation.html"}]};
