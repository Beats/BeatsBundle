<?php
namespace BeatsBundle\Security\Core\User;

use BeatsBundle\Helper\UTC;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class Member implements AdvancedUserInterface, EquatableInterface {

  private $id;
  private $identity;
  private $provider;
  private $timezone;
  private $refreshed;

  private $user;

  private $username;
  private $password;
  private $salt;
  private $enabled;
  private $accountNonExpired;
  private $credentialsNonExpired;
  private $accountNonLocked;
  private $roles;

  public function __construct(
    $id, array $roles = array(),
    $username, $password, $salt,
    $enabled = true, $userNonExpired = true, $credentialsNonExpired = true, $userNonLocked = true,
    $user = null, $identity = null, $provider = null, $timezone = null
  ) {
//    if (empty($username)) {
//      throw new \InvalidArgumentException('The username cannot be empty.');
//    }

    $this->id        = $id;
    $this->identity  = $identity;
    $this->provider  = $provider;
    $this->timezone  = $timezone;
    $this->refreshed = UTC::createDateTime();

    $this->username              = $username;
    $this->password              = $password;
    $this->salt                  = $salt;
    $this->enabled               = $enabled;
    $this->accountNonExpired     = $userNonExpired;
    $this->credentialsNonExpired = $credentialsNonExpired;
    $this->accountNonLocked      = $userNonLocked;
    $this->roles                 = $roles;

    $this->user = $user;
  }

  /********************************************************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * {@inheritdoc}
   */
  public function getSalt() {
    return $this->salt;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * {@inheritdoc}
   */
  public function isAccountNonExpired() {
    return $this->accountNonExpired;
  }

  /**
   * {@inheritdoc}
   */
  public function isAccountNonLocked() {
    return $this->accountNonLocked;
  }

  /**
   * {@inheritdoc}
   */
  public function isCredentialsNonExpired() {
    return $this->credentialsNonExpired;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function eraseCredentials() {
//    $this->password = null;
  }

  /********************************************************************************************************************/

  /**
   * The equality comparison should neither be done by referential equality
   * nor by comparing identities (i.e. getId() === getId()).
   *
   * However, you do not need to compare every attribute, but only those that
   * are relevant for assessing whether re-authentication is required.
   *
   * Also implementation should consider that $user instance may implement
   * the extended user interface `AdvancedUserInterface`.
   *
   * @param UserInterface $user
   *
   * @return Boolean
   */
  public function isEqualTo(UserInterface $user) {
    if ($user instanceof self) {
      return $user->getID() == $this->getID();
    } else {
      return !strcmp($this->username, $user->getUsername());
    }
  }

  /********************************************************************************************************************/

  /**
   * @return mixed
   */
  public function getID() {
    return $this->id;
  }

  /**
   * @return mixed
   */
  public function getIdentity() {
    return $this->identity;
  }

  /**
   * @return string
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * @return string
   */
  public function getTimezone() {
    return $this->timezone;
  }

  /**
   * @return \DateTime
   */
  public function getRefreshed() {
    return $this->refreshed;
  }

  /**
   * @return Member
   */
  public function invalidate() {
    $this->getRefreshed()->setTimestamp(0);
    return $this;
  }

  /********************************************************************************************************************/

  /**
   * @return \BeatsBundle\Security\User\UserInterface
   */
  public function getUser() {
    return $this->user;
  }

  /********************************************************************************************************************/

}