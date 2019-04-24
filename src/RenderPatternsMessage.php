<?php

namespace Drupal\render_patterns;

/**
 * A pattern used to print messages.
 */
class RenderPatternsMessage extends Pattern {

  protected $properties = [
    'message' => [
      'type' => 'string',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#markup' => $this->message,
    ];
  }

}
