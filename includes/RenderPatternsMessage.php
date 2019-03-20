<?php

/**
 * A pattern used to print messages.
 */
class RenderPatternsMessage extends RenderPatternsPattern {

  protected $properties = [
    'message' => [
      'type' => 'string',
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
