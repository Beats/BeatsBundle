<?php

namespace BeatsBundle\Security\Http\Firewall;

use BeatsBundle\OAuth\ResourceProviderMap;
use BeatsBundle\Security\Core\Authentication\Token\OAuthToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;

class OAuthListener extends AbstractAuthenticationListener {

  /**
   * @var ResourceProviderMap
   */
  private $_providers;

  /**
   * @var ResourceProviderMap $providers
   */
  public function setProviders(ResourceProviderMap $providers) {
    $this->_providers = $providers;
  }

  public function requiresAuthentication(Request $request) {
    return $this->_providers->requiresAuthentication($request);
  }

  /**
   * {@inheritDoc}
   */
  protected function attemptAuthentication(Request $request) {

    $provider = $this->_providers->byRequest($request)->validate($request);

    $accessToken = $provider->getAccessToken($request);

    $token = new OAuthToken($accessToken, $provider->getName());

    return $this->authenticationManager->authenticate($token);
  }

}
