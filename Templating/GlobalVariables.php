<?php

namespace BeatsBundle\Templating;

use BeatsBundle\Helper\UTC;
use BeatsBundle\Security\Core\User\Member;
use BeatsBundle\Security\User\UserInterface;
use BeatsBundle\Service\Aware\ValidatorAware;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * GlobalVariables is the entry point for Symfony global variables in Twig templates.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class GlobalVariables extends \Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables {
  use ValidatorAware;

  /**
   * @return SecurityContextInterface
   * @throws ServiceNotFoundException
   */
  protected function _security() {
    return $this->container->get('security.context');
  }

  /**
   * @return Request
   */
  protected function _request() {
    return $this->container->get('request');
  }

  /********************************************************************************************************************/

  /**
   * Returns the security context service.
   *
   * @return SecurityContextInterface|void The security context
   */
  public function getSecurity() {
    return $this->container->get('security.context');
  }

  /**
   * Returns the current request.
   *
   * @return Request|void The http request object
   */
  public function getRequest() {
    return $this->container->get('request');
  }

  /**
   * Returns the current session.
   *
   * @return Session|void The session
   */
  public function getSession() {
    return $this->container->get('session');
  }

  /**
   * Returns the current app environment.
   *
   * @return string The current environment string (e.g 'dev')
   */
  public function getEnvironment() {
    return $this->container->getParameter('kernel.environment');
  }

  /**
   * Returns the current app debug mode.
   *
   * @return boolean The current debug mode
   */
  public function getDebug() {
    return (bool)$this->container->getParameter('kernel.debug');
  }

  /**
   * @return \BeatsBundle\Validation\Validator
   */
  public function getValidator() {
    return $this->_validator();
  }

  /********************************************************************************************************************/

  protected function _extractUser() {
    $token = $this->_security()->getToken();
    if (empty($token) || !$token->isAuthenticated()) {
      return null;
    }
    $user = $token->getUser();
    if (is_string($user)) {
      return null;
    }

    return $user;
  }

  protected function _loadUser($identity = null) {
    $identity = $this->_request()->cookies->get('beats_test_identity', $identity);
    if (empty($identity)) {
      return null;
    }

    return $this->container->get('beats.security.user.provider')->loadUserByUsername($identity);
  }


  /**
   * @return Member|null
   */
  abstract public function getMember();

  /**
   * Returns the current user.
   *
   * @return UserInterface|void
   */
  public function getUser() {
    $member = $this->getMember();
    if (empty($member)) {
      return null;
    }

    return $member->getUser();
  }

  public function isCurrent($memberID) {
    $member = $this->getMember();

    return !(empty($member) || strcasecmp($member->getID(), $memberID));
  }

  public function isAnonymous() {
    $member = $this->getMember();

    return empty($member);
  }

  public function isAuthenticated() {
    return !$this->isAnonymous();
  }

  public function getUTC($format = 'Y-m-d H:i:s', $time = null) {
    return UTC::toFormat($format, $time);
  }

  public function getUTCTimestamp($time = null) {
    return $this->getUTC('Y-m-d H:i:s', $time);
  }

  /********************************************************************************************************************/

  public function get_field($path) {
    return $this->_validator()->getMessages()->get($path, null, true);
  }

  public function get_value($path, $default = null) {
    $field = $this->get_field($path);
    if (empty($field)) {
      return $this->_request()->get($path, $default, true);
    }

    return $field->getValue();
  }

  /********************************************************************************************************************/

  public function getLocale() {
    return $this->container->get('translator')->getLocale();
  }

  public function getLang() {
    $locale = $this->getLocale();
//    language[_territory][.codeset][@modifier]
    preg_match('#(?<lang>\w{2})(_(?<_territory>\w+))?(.(?<codeset>\w+))?(.(?<modifier>\w+))?#', $locale, $matches);

    return $matches['lang'];
  }

  /********************************************************************************************************************/

  /********************************************************************************************************************/

}
