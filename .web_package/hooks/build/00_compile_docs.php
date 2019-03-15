<?php

/**
 * @file
 * Generates documentation, adjusts paths and adds to SCM.
 */

namespace AKlump\WebPackage;

$build
  ->setDocumentationSource('documentation')
  ->generateDocumentationTo('docs')
  ->addFilesToScm([
    'README.md',
    'README.txt',
  ])
  ->displayMessages();
