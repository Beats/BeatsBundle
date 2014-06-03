<?php
namespace BeatsBundle\Security\User;

use BeatsBundle\Helper\UTC;
use BeatsBundle\Security\Core\User\Member;
use BeatsBundle\Security\PersisterInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider extends ContainerAware implements UserProviderInterface {

  const USER_CLASS = 'BeatsBundle\Security\Core\User\Member';

  const REFRESH_RATE = '-2 hour';


  /********************************************************************************************************************/

  /**
   * @var string
   */
  private $_persisterServiceID;

  public function __construct(ContainerInterface $container, $serviceID) {
    $this->setContainer($container);
    $this->_persisterServiceID = $serviceID;
  }

  /********************************************************************************************************************/

  /**
   * @return Request
   */
  final protected function _request() {
    return $this->container->get('request');
  }

  /**
   * @return SessionInterface
   */
  final protected function _session() {
    return $this->container->get('session');
  }

  /**
   * @return PersisterInterface
   */
  final protected function _persister() {
    return $this->container->get($this->_persisterServiceID);
  }

  /********************************************************************************************************************/

  public function supportsClass($class) {
    return !strcmp($class, static::USER_CLASS);
  }

  public function loadUserByUsername($identity) {
    $md = $this->_persister();

    $kind = $this->_request()->get('_auth_kind', 0);
    $auth = $md->findAuth(strtolower(trim($identity)), strtolower(trim($kind)));
    $user = $md->findUserByID($auth->getUserID());
    return $md->buildMember($user, $auth, static::USER_CLASS);
  }

  /**
   * Refreshes the user for the account interface.
   *
   * It is up to the implementation to decide if the user data should be
   * totally reloaded (e.g. from the database), or if the UserInterface
   * object can just be merged into some internal array of users / identity
   * map.
   *
   * @param UserInterface $user
   *
   * @return UserInterface
   *
   * @throws UnsupportedUserException if the account is not supported
   */
  public function refreshUser(UserInterface $user) {
    $user = $this->_validateMember($user);
    /** @noinspection PhpParamsInspection */
    if ($this->_userSessionExpired($user)) {
      return $this->_doRefreshUser($user);
    }
    return $user;
  }

  /********************************************************************************************************************/

  protected function _validateMember(UserInterface $user) {
    if ($this->supportsClass(get_class($user))) {
      return $user;
    }
    throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
  }

  protected function _userSessionExpired(Member $user) {
    return $user->getRefreshed() <= UTC::createDateTime(self::REFRESH_RATE);
  }

  protected function _doRefreshUser(UserInterface $user) {
    if ($user instanceof Member) {
      $md = $this->_persister();

      $auth = $md->findAuth($user->getIdentity(), $user->getProvider());
      $user = $md->findUserByID($auth->getUserID());
      return $md->buildMember($user, $auth, static::USER_CLASS);
    }
    return $this->loadUserByUsername($user->getUsername());
  }

  /********************************************************************************************************************/

}