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
