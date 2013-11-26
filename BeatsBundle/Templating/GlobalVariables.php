<?php

namespace BeatsBundle\Templating;

use BeatsBundle\Helper\UTC;
use BeatsBundle\Security\Core\User\Member;
use BeatsBundle\Security\User\UserInterface;
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

  abstract public function isAdmin();

  abstract public function isFront();

  /********************************************************************************************************************/


  /**
   * @return \Symfony\Component\Security\Core\User\User|null
   */
  public function getAdmin() {
    if ($this->isAdmin()) {
      return $this->_extractUser();
    }
    return null;
  }

  /**
   * @return Member|null
   */
  public function getMember() {
    if ($this->isAdmin()) {
      return $this->_loadUser();
    }
    return $this->_extractUser();
  }

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

  public function getUTC($format = 'Y-m-d H:i:s', $time = null) {
    return UTC::toFormat($format, $time);
  }

}
