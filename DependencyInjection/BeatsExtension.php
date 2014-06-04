<?php

namespace BeatsBundle\DependencyInjection;

use BeatsBundle\Service\Mailer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class BeatsExtension extends Extension {
  /**
   * {@inheritDoc}
   */
  public function load(array $configs, ContainerBuilder $container) {
    $config = $this->processConfiguration(new Configuration(), $configs);

    $container->setParameter('beats.dbal.rdb', $config['dbal']['rdb']);
    $container->setParameter('beats.dbal.dom', $config['dbal']['dom']);

    $container->setParameter('beats.oauth.providers', $config['oauth']['providers']);
    $container->setParameter('beats.oauth.callback', $config['oauth']['callback']);

    $container->setParameter('beats.mailer', $config['mailer']);
    $container->setParameter('beats.flasher', $config['flasher']);
    $container->setParameter('beats.chronos', $config['chronos']);

    $container->setParameter('beats.security.service.id', $config['security']['persister']);
    $container->setParameter('beats.security.default', $config['security']['default']);

    $container->setParameter('beats.translation.locales', $config['translation']['locales']);

    // All service parameters and configuration should be
    // defined in /app/config/beats.yml file
    // This is where we combine the persisted configurations
    // and service instantiations.

    $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
    $loader->load('services.xml');
    $loader->load('oauth.xml');

  }
}
