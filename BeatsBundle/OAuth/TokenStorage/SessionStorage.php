<?php

namespace BeatsBundle\OAuth\TokenStorage;


use BeatsBundle\OAuth\ResourceProviderInterface;
use BeatsBundle\OAuth\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionStorage implements TokenStorageInterface {

  /**
   * @var SessionInterface
   */
  private $_session;


  /**
   * @param SessionInterface $session
   */
  public function __construct(SessionInterface $session) {
    $this->_session = $session;
  }

  public function fetch(ResourceProviderInterface $provider, $tokenID) {
    $key   = $this->_key($provider, $tokenID);
    $token = $this->_session->get($key);
    if (null === $token) {
      throw new \RuntimeException('No request token available in storage.');
    }
    // request tokens are one time use only
    $this->_session->remove($key);
    return $token;
  }

  public function save(ResourceProviderInterface $provider, array $token) {
    if (!isset($token['oauth_token'])) {
      throw new \RuntimeException('Invalid request token.');
    }
    $key = $this->_key($provider, $token['oauth_token']);
    $this->_session->set($key, $token);
  }

  /**
   * Key to for fetching or saving a token.
   *
   * @param ResourceProviderInterface $provider
   * @param mixed $tokenID
   *
   * @return string
   */
  protected function _key(ResourceProviderInterface $provider, $tokenID) {
    return implode('.', array('_oauth', 'request_token', $provider->getName(), $provider->getClientID(), $tokenID));
  }
}
