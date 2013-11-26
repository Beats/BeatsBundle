<?php

namespace BeatsBundle;

use BeatsBundle\DependencyInjection\Security\Factory\OAuthFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BeatsBundle extends Bundle {

  public function build(ContainerBuilder $container) {
    parent::build($container);

    $extension = $container->getExtension('security');
    /** @noinspection PhpUndefinedMethodInspection */
    $extension->addSecurityListenerFactory(new OAuthFactory());
  }
}
