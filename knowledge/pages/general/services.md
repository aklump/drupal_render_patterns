<!--
id: services
tags: ''
-->

# Using Services (Dependency Injection)

This example shows you how to use services in your pattern.

1. Implement `\Drupal\Core\DependencyInjection\ContainerInjectionInterface`
1. Declare `private` class variables.

```php
namespace Drupal\my_theme\RenderPatterns\;

final class LibraryFacets extends Pattern implements \Drupal\Core\DependencyInjection\ContainerInjectionInterface {

  private $blockRepository;

  private $entityTypeManager;

  public function __construct(\Drupal\block\BlockRepositoryInterface $block_repository, \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager) {
    $this->blockRepository = $block_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    return new static(
      $container->get('block.repository'),
      $container->get('entity_type.manager'),
    );
  }
  
  ...
}

```
