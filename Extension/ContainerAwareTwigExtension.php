<?php
namespace BeatsBundle\Extension;

use BeatsBundle\Service\Aware\FlasherAware;
use BeatsBundle\Service\Aware\FSALAware;
use BeatsBundle\Service\Aware\ValidatorAware;
use BeatsBundle\Service\Service;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Environment;
use Twig_Extension;

class ContainerAwareTwigExtension extends Twig_Extension implements ContainerAwareInterface {
  use Service, FSALAware, FlasherAware, ValidatorAware;

  /*********************************************************************************************************************/

  /**
   * @var ContainerInterface
   */
  protected $container;

  public function __construct(ContainerInterface $container) {
    $this->setContainer($container);
  }

  public function setContainer(ContainerInterface $container = null) {
    $this->container = $container;
  }

  public function getName() {
    return get_class($this);
  }

  /*********************************************************************************************************************/

  /**
   * @return Twig_Environment
   */
  final protected function _twig() {
    return $this->container->get('twig');
  }

  /*********************************************************************************************************************/

}
