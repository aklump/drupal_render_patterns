# Patterns and Dependency Injection

Patterns that need services from the service container should implement the `create` method and a custom `__construct`.

      /**
       * {@inheritdoc}
       */
      public static function create(ContainerInterface $container) {
        return new static(
          $container->get('data_api')
        );
      }
