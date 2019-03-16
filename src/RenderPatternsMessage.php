<?php

namespace Drupal\render_patterns\Pattern;

use Drupal\render_patterns\Pattern;

/**
 * A pattern used to print messages.
 */
class RenderPatternsMessage extends Pattern {

  protected $schema = [
    'properties' => [
      'message' => [
        'type' => 'string',
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->message,
    ];
  }

}
