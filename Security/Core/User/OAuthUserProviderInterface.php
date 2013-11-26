<?php

namespace BeatsBundle\Security\Core\User;

use BeatsBundle\Security\Core\Authentication\Token\OAuthToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

interface OAuthUserProviderInterface extends UserProviderInterface {

  /**
   * Loads the user from an OAuth token
   *
   * @param OAuthToken $token
   *
   * @return UserInterface
   *
   * @throws UsernameNotFoundException if the user is not found
   */
  public function loadUserByOAuthToken(OAuthToken $token);

}
