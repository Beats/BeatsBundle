<?php

namespace BeatsBundle\OAuth\ResourceProvider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

abstract class OAuth2 extends AbstractResourceProvider {

  /********************************************************************************************************************/

  public function supports(Request $request) {
    return $request->query->has('code');
  }

  /********************************************************************************************************************/

  public function getUserInformation($accessToken) {
    $url = $this->_urlUserInfo(array(
      'access_token' => $accessToken
    ));

    $response = $this->_requestUserInformation($url);

    $data = $this->_parseResponse($response);

    return $this->_setupUserInformation($accessToken, $data);
  }

  public function getAuthorizationUrl($uri, array $extraParameters = array()) {
    return $this->_urlAuthorization(array_merge($extraParameters, array(
      'response_type' => 'code',
      'client_id'     => $this->getClientID(),
      'scope'         => $this->getScope(),
      'redirect_uri'  => $uri,
    )));
  }

  protected function _paramsAccessToken(Request $request = null, $callbackURI = null, array $parameters = array()) {
    if (empty($request)) {
      $request = $this->_request();
    }
    return array_merge($parameters, array(
      'code'          => $request->query->get('code'),
      'grant_type'    => 'authorization_code',
      'client_id'     => $this->getClientID(),
      'client_secret' => $this->_getClientSecret(),
      'redirect_uri'  => empty($callbackURI) ? $this->getCheckPath() : $callbackURI,
    ));
  }

  public function getAccessToken(Request $request = null, $callbackURI = null, array $parameters = array()) {

    $response = $this->_requestAccessToken(
      $this->_urlAccessToken(), $this->_paramsAccessToken($request, trim($callbackURI), $parameters)
    );

    $response = $this->_parseResponse($response);

    if (isset($response['error'])) {
      throw new AuthenticationException(sprintf('OAuth error: "%s"', is_string($response['error']) ? $response['error'] : @$response['message']));
    }

    if (!isset($response['access_token'])) {
      throw new AuthenticationException('Not a valid access token.');
    }

    return $response['access_token'];
  }

  /********************************************************************************************************************/

  protected function _requestAccessToken($url, array $parameters = array()) {
    return $this->_send($url, http_build_query($parameters));
  }

  protected function _requestUserInformation($url, array $parameters = array()) {
    return $this->_send($url);
  }

}
