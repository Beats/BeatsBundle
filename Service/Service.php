<?php
namespace BeatsBundle\Service;

use BeatsBundle\Model\AbstractModel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface as Templater;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

trait Service {

  final protected function _isProduction() {
    return $this->_kernel()->getEnvironment() == 'prod';
  }

  /**
   * @return Kernel
   */
  final protected function _kernel() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('kernel');
  }

  /**
   * @return SecurityContextInterface
   */
  final protected function _security() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('security.context');
  }

  /**
   * @return LoggerInterface
   */
  final protected function _logger() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('logger');
  }

  /**
   * @return Templater
   */
  final protected function _templater() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('templating');
  }

  /**
   * @return Translator
   */
  final protected function _translator() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('translator');
  }

  /**
   * @return Mailer
   */
  final protected function _mailer() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('beats.mailer');
  }

  /*********************************************************************************************************************/

  /**
   * @return Session
   */
  final protected function _session() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('session');
  }

  /*********************************************************************************************************************/

  /**
   * @return Request
   */
  final protected function _request() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('request');
  }

  /**
   * @return RouterInterface
   */
  final protected function _router() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('router');
  }

  /*********************************************************************************************************************/

  /**
   * @return Chronos
   */
  final protected function _chronos() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('beats.chronos');
  }

  /**
   * Returns a business logic model
   * @param null|string $bundle
   * @param string $name
   * @return AbstractModel
   */
  final protected function _model($name, $bundle = null) {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get(empty($bundle) ? "beats.model.$name" : "beats.model.$bundle.$name");
  }

  /*********************************************************************************************************************/

  final protected function _trans($id, array $parameters = array(), $domain = null, $locale = null) {
    return $this->_translator()->trans($id, $parameters, $domain, $locale);
  }
}






