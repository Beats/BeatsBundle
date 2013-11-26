<?php

namespace BeatsBundle\OAuth;

use BeatsBundle\Security\User\InfoInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

interface ResourceProviderInterface {

  /**
   * Return a name for the resource provider.
   *
   * @return string
   */
  public function getName();

  /**
   * Returns the provider's unique oauth client id
   *
   * @return string
   */
  public function getClientID();

  /**
   * Returns the provider's access scope
   *
   * @return string
   */
  public function getScope();

  /**
   * Returns URL path that the provider is expecting the redirect callback request
   *
   * @return string
   */
  public function getCheckPath();

  /**
   * Checks whether the provider can handle the request.
   *
   * @param Request $request
   *
   * @return boolean
   */
  public function supports(Request $request);

  /**
   * Validates the OAuth callback redirect request for errors and if supported
   *
   * @param Request $request
   *
   * @throws AuthenticationException
   *
   * @return ResourceProviderInterface
   */
  public function validate(Request $request);

  /**
   * Returns the provider's authorization url
   *
   * @param mixed $uri     The uri to redirect the client back to
   * @param array $extraParameters An array of parameters to add to the url
   *
   * @return string The authorization url
   */
  public function getAuthorizationUrl($uri, array $extraParameters = array());

  /**
   * Retrieve an access token for a given code
   *
   * @param Request $request         The request object where is going to extract the code from
   * @param mixed $callbackURI     The uri to redirect the client back to
   * @param array $extraParameters An array of parameters to add to the url
   *
   * @return string The access token
   */
  public function getAccessToken(Request $request = null, $callbackURI = null, array $extraParameters = array());

  /**
   * Retrieves the user's information from an access_token
   *
   * @param string $accessToken
   *
   * @return InfoInterface The wrapped response interface.
   */
  public function getUserInformation($accessToken);

  /**
   * Returns url used for creating a user account based on the external user provider
   *
   * @return string
   */
  public function urlConnect();

}
