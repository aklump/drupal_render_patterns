
## Update your Render Patterns

You patterns will be broken when you update to 2.x, here's what you need to do.

1. Search for any overridden constructors...

        public function __construct(
          Data $data,
          BlockRepositoryInterface $block_repository,
        ) {
          $this->blockRepository = $block_repository;
          parent::__construct($data);
        }
    
        public static function create(ContainerInterface $container) {
          return new static(
            $container->get('data_api'),
            $container->get('block.repository'),
          );
        }
  
1. ... and remove the references to `\AKlump\Data\Data`; so the above becomes the following...

        public function __construct(
          BlockRepositoryInterface $block_repository,
          EntityTypeManagerInterface $entity_type_manager
        ) {
          $this->blockRepository = $block_repository;
          parent::__construct();
        }
    
        public static function create(ContainerInterface $container) {
          return new static(
            $container->get('block.repository'),
          );
        }  

## To maintain compatibility

You can create a interim class like the following and update all your render patterns to extend it, rather than `\Drupal\render_patterns\Pattern`.

        <?php
        
        namespace Drupal\my_module;
        
        use AKlump\Data\DataInterface;
        use Drupal\data_api\DataTrait;
        use Drupal\render_patterns\Pattern;
        use Symfony\Component\DependencyInjection\ContainerInterface;
        
        /**
         * An example showing how to maintain backwards compatibility.
         */
        abstract class PatternWithData extends Pattern {
        
          use DataTrait;
        
          /**
           * RenderPatternsPattern constructor.
           */
          public function __construct(DataInterface $dataApiData) {
            $this->setDataApiData($dataApiData);
            parent::__construct();
          }
        
          /**
           * {@inheritdoc}
           */
          public static function create(ContainerInterface $container) {
            return new static(
              $container->get('data_api')
            );
          }
        
        }
