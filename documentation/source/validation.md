# Property Validation

Validation is based on [JSON Schema](https://json-schema.org/latest/json-schema-validation.html).  However we're not using JSON at all, but rather the `pattern::$properties` property on your class.  The API structured however follows JSON schema and you will build an array following those guidelines.  In most cases, `$properties` is the only key of the schema specification you will need to use.  However if you find some reason to need an entire schema, then set `pattern::$schema` instead and do not set `pattern::$properties`.  **The `required` keys is ignored when validating a single key.**

      protected $properties = [
        'title' => [
          'type' => 'string',
          'default' => 'Title Goes Here',
        ],
        'tag' => [
          'type' => 'string',
          'enum' => [
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
          ],
          'default' => 'h1',
        ],
      ];

## `description` not comments

Instead of commenting your code, add a `description` key to make a property note or descriptio.

Wrong:

    protected $properties = [
      // Leave this null if the first batch is already loaded. For autoclick loading, set this to an integer total number to load.  The caller should most likely be checking $_GET for this value!!!
      'load' => [
        'type' => ['null', 'integer'],
        'default' => NULL,
      ],
    ];

Right:

    protected $properties = [
      'load' => [
        'description' => 'Leave this null if the first batch is already loaded. For autoclick loading, set this to an integer total number to load.  The caller should most likely be checking $_GET for this value!!!',
        'type' => ['null', 'integer'],
        'default' => NULL,
      ],
    ];
