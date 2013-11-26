<?php

namespace BeatsBundle\Security\Core\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Role\Role;

class OAuthToken extends AbstractToken {

  /**
   * @var string
   */
  private $_accessToken;

  /**
   * @var string
   */
  private $_provider;

  /**
   * @param array|Role[] $accessToken
   * @param $provider
   * @param array $roles
   */
  public function __construct($accessToken, $provider, array $roles = array()) {
    parent::__construct($roles);

    $this->_accessToken = $accessToken;
    $this->_provider    = $provider;

    parent::setAuthenticated(count($roles) > 0);
  }

  public function getCredentials() {
    return '';
  }


  /**
   * Returns the OAuth access token
   *
   * @return string
   */
  public function getAccessToken() {
    return $this->_accessToken;
  }

  /**
   * Get the OAuth resource provider code
   *
   * @return string
   */
  public function getProvider() {
    return $this->_provider;
  }


  public function serialize() {
    return serialize(array(
      $this->_accessToken, $this->_provider, parent::serialize()
    ));
  }

  public function unserialize($serialized) {
    list($this->_accessToken, $this->_provider, $parent) = unserialize($serialized);
    parent::unserialize($parent);
  }

}
