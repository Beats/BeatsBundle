<?php
namespace BeatsBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware as Base;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ContainerAware extends Base {
  use Service;

  const USER_AGENT = 'Symfony Beats (http://www.beats.rs)';

  /**
   * @var ParameterBag
   */
  protected $_options;

  public function __construct(ContainerInterface $container, array $options = array()) {
    $this->setContainer($container);
    $this->_options = new FrozenParameterBag($options);
  }

}






