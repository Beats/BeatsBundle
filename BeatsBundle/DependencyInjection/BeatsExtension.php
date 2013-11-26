<?php

namespace BeatsBundle\DependencyInjection;

use BeatsBundle\Service\Chronos;
use BeatsBundle\Service\Mailer;
use BeatsBundle\Session\Flasher;
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

    $container->setParameter('beats.rdb', $config['rdb']);
    $container->setParameter('beats.dom', $config['dom']);

    $container->setParameter('beats.oauth.providers', $config['oauth']['providers']);
    $container->setParameter('beats.oauth.callback', $config['oauth']['callback']);

    $container->setParameter(Mailer::CONFIG_MAILER_MAILS, $config['mailer']['mails']);

//    $container->setParameter(Mailer::CONFIG_MAIL_FROM, $config['mailer']['from']);
//    $container->setParameter(Mailer::CONFIG_MAIL_NAME, $config['mailer']['name']);
    $container->setParameter(Chronos::CONFIG_TIMEZONE, $config['chronos']['timezone']);
    $container->setParameter(Flasher::CONFIG_TEMPLATE, $config['flasher']['template']);

    $container->setParameter('beats.security.service.id', $config['security']['persister']);
    $container->setParameter('beats.security.default', $config['security']['default']);

    // All service parameters and configuration should be
    // defined in /app/config/beats.yml file
    // This is where we combine the persisted configurations
    // and service instantiations.

    $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
    $loader->load('services.xml');
    $loader->load('oauth.xml');

  }
}
