<?php

/** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
$dispatcher->addListener(\AKlump\Knowledge\Events\GetVariables::NAME, function (\AKlump\Knowledge\Events\GetVariables $event) {
  $version_file = $event->getPathToSource() . '/../render_patterns.info.yml';
  $info = \Symfony\Component\Yaml\Yaml::parseFile($version_file);
  $event->setVariable('version', $info['version'] ?? NULL);
});
