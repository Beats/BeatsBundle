<?php

namespace BeatsBundle\Security\Core\Authentication\Provider;

use BeatsBundle\OAuth\ResourceProviderMap;
use BeatsBundle\Security\Core\Authentication\Token\OAuthToken;
use BeatsBundle\Security\Core\User\OAuthUserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

class OAuthProvider implements AuthenticationProviderInterface {

  /**
   * @var ResourceProviderMap
   */
  private $_providers;

  /**
   * @var OAuthUserProviderInterface
   */
  private $_userProvider;

  /**
   * @var UserCheckerInterface
   */
  private $_userChecker;

  /**
   * @param OAuthUserProviderInterface $userProvider     User provider
   * @param ResourceProviderMap             $providers        Resource provider map
   * @param UserCheckerInterface            $userChecker      User checker
   */
  public function __construct(ResourceProviderMap $providers, OAuthUserProviderInterface $userProvider, UserCheckerInterface $userChecker) {
    $this->_providers    = $providers;
    $this->_userProvider = $userProvider;
    $this->_userChecker  = $userChecker;
  }

  public function supports(TokenInterface $token) {
    return $token instanceof OAuthToken;
  }

  public function authenticate(TokenInterface $token) {
    /** @noinspection PhpParamsInspection */
    return $this->_doAuthenticate($token);
  }

  private function _doAuthenticate(OAuthToken $token) {

    $provider = $this->_providers->byName($token->getProvider());

    $user = $this->_userProvider->loadUserByOAuthToken($token, $provider);

    $this->_userChecker->checkPreAuth($user);
    $this->_userChecker->checkPostAuth($user);

    $token = new OAuthToken($token->getAccessToken(), $provider->getName(), $user->getRoles());
    $token->setUser($user);
    $token->setAuthenticated(true);

    return $token;
  }

}
