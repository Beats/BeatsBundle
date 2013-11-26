<?php

namespace BeatsBundle\OAuth\ResourceProvider;

use Buzz\Message\RequestInterface;
use BeatsBundle\OAuth\TokenStorage\SessionStorage;
use BeatsBundle\OAuth\TokenStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

abstract class OAuth1 extends AbstractResourceProvider {

  const SIGNATURE_METHOD_HMAC = 'HMAC-SHA1';
  const SIGNATURE_METHOD_RSA  = 'RSA-SHA1';

  /**
   * Sign the request parameters
   *
   * @param string $method          Request method
   * @param string $url             Request url
   * @param array  $parameters      Parameters for the request
   * @param string $clientSecret    Client secret to use as key part of signing
   * @param string $tokenSecret     Optional token secret to use with signing
   * @param string $signatureMethod Optional signature method used to sign token
   *
   * @return string
   *
   * @throws \RuntimeException
   */
  public static function signRequest($method, $url, $parameters, $clientSecret, $tokenSecret = '', $signatureMethod = self::SIGNATURE_METHOD_HMAC) {
    // Validate required parameters
    foreach (array('oauth_consumer_key', 'oauth_timestamp', 'oauth_nonce', 'oauth_version', 'oauth_signature_method') as $parameter) {
      if (!isset($parameters[$parameter])) {
        throw new \RuntimeException(sprintf('Parameter "%s" must be set.', $parameter));
      }
    }

    // Remove oauth_signature if present
    // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
    if (isset($parameters['oauth_signature'])) {
      unset($parameters['oauth_signature']);
    }

    // Parameters are sorted by name, using lexicographical byte value ordering.
    // Ref: Spec: 9.1.1 (1)
    uksort($parameters, 'strcmp');

    // http_build_query should use RFC3986
    $parts = array(
      $method,
      rawurlencode($url),
      rawurlencode(str_replace(array('%7E', '+'), array('~', '%20'), http_build_query($parameters))),
    );

    $baseString = implode('&', $parts);

    switch ($signatureMethod) {
      case self::SIGNATURE_METHOD_HMAC:
        $keyParts = array(
          rawurlencode($clientSecret),
          rawurlencode($tokenSecret),
        );

        $signature = hash_hmac('sha1', $baseString, implode('&', $keyParts), true);
        break;

      case self::SIGNATURE_METHOD_RSA:
        $privateKey = openssl_pkey_get_private(file_get_contents($clientSecret), $tokenSecret);
        $signature  = false;

        openssl_sign($baseString, $signature, $privateKey);
        openssl_free_key($privateKey);
        break;

      default:
        throw new \RuntimeException(sprintf('Unknown signature method selected %s.', $signatureMethod));
    }

    return base64_encode($signature);
  }

  /********************************************************************************************************************/

  /**
   * @var TokenStorageInterface
   */
  protected $_storage;

  public function __construct(ContainerInterface $container, $name, array $options = array()) {
    parent::__construct($container, $name, array_merge(array(
      'client_id'        => '',
      'client_secret'    => '',
      'realm'            => null,
      'signature_method' => 'HMAC-SHA1',
    ), $options));
    /** @noinspection PhpParamsInspection */
    $this->_storage = new SessionStorage($this->container->get('session'));
  }

  /********************************************************************************************************************/

  public function supports(Request $request) {
    return $request->query->has('oauth_token');
  }

  /********************************************************************************************************************/

  public function getUserInformation($accessToken) {
    $parameters = array(
      'oauth_consumer_key'     => $this->getClientID(),
      'oauth_timestamp'        => time(),
      'oauth_nonce'            => $this->_generateNonce(),
      'oauth_version'          => '1.0',
      'oauth_signature_method' => $this->_getSignatureMethod(),
      'oauth_token'            => $accessToken['oauth_token'],
    );

    $url = $this->_urlUserInfo();

    $parameters['oauth_signature'] = self::signRequest(
      'GET',
      $url,
      $parameters,
      $this->_getClientSecret(),
      $accessToken['oauth_token_secret'],
      $this->_getSignatureMethod()
    );

    $response = $this->_requestUserInformation($url, $parameters);

    $data = $this->_parseResponse($response);

    return $this->_setupUserInformation($accessToken, $data);
  }

  public function getAuthorizationUrl($uri, array $extraParameters = array()) {
    $token = $this->_getRequestToken($uri, $extraParameters);
    return $this->_urlAuthorization(array('oauth_token' => $token['oauth_token']));
  }

  public function getAccessToken(Request $request = null, $callbackURI = null, array $extraParameters = array()) {
    if (empty($request)) {
      $request = $this->_request();
    }

    if (null === $requestToken = $this->_storage->fetch($this, $request->query->get('oauth_token'))) {
      throw new \RuntimeException('No request token found in the storage.');
    }

    $parameters = array_merge($extraParameters, array(
      'oauth_consumer_key'     => $this->getClientID(),
      'oauth_timestamp'        => time(),
      'oauth_nonce'            => $this->_generateNonce(),
      'oauth_version'          => '1.0',
      'oauth_signature_method' => $this->_getSignatureMethod(),
      'oauth_token'            => $requestToken['oauth_token'],
      'oauth_verifier'         => $request->query->get('oauth_verifier'),
    ));

    $url = $this->_urlAccessToken();

    $parameters['oauth_signature'] = self::signRequest(
      'POST',
      $url,
      $parameters,
      $this->_getClientSecret(),
      $requestToken['oauth_token_secret'],
      $this->_getSignatureMethod()
    );

    $response = $this->_requestAccessToken($url, $parameters);

    $response = $this->_parseResponse($response);

    if (isset($response['oauth_problem'])) {
      throw new AuthenticationException(sprintf('OAuth error: "%s"', $response['oauth_problem']));
    }

    if (!isset($response['oauth_token']) || !isset($response['oauth_token_secret'])) {
      throw new AuthenticationException('Not a valid request token.');
    }

    return $response;
  }

  /********************************************************************************************************************/

  /**
   * Generate a non-guessable nonce value.
   *
   * @return string
   */
  protected function _generateNonce() {
    return md5(microtime() . mt_rand());
  }

  protected function _getSignatureMethod() {
    return $this->_options->get('signature_method');
  }

  protected function _getRequestToken($redirectUri, array $extraParameters = array()) {
    $timestamp = time();

    $parameters = array_merge($extraParameters, array(
      'oauth_consumer_key'     => $this->getClientID(),
      'oauth_timestamp'        => $timestamp,
      'oauth_nonce'            => $this->_generateNonce(),
      'oauth_version'          => '1.0',
      'oauth_callback'         => $redirectUri,
      'oauth_signature_method' => $this->_getSignatureMethod(),
    ));

    $url = $this->_options->get('request_token_url');

    $parameters['oauth_signature'] = self::signRequest(
      'POST',
      $url,
      $parameters,
      $this->_getClientSecret(),
      '',
      $this->_getSignatureMethod()
    );

    $apiResponse = $this->_requestToken($url, $parameters);

    $response = $this->_parseResponse($apiResponse);

    if (isset($response['oauth_problem']) || (isset($response['oauth_callback_confirmed']) && ($response['oauth_callback_confirmed'] != 'true'))) {
      throw new AuthenticationException(sprintf('OAuth error: "%s"', $response['oauth_problem']));
    }

    if (!isset($response['oauth_token']) || !isset($response['oauth_token_secret'])) {
      throw new AuthenticationException('Not a valid request token.');
    }

    $response['timestamp'] = $timestamp;

    $this->_storage->save($this, $response);

    return $response;
  }

  protected function _setupAuthorizationHeader(array $parameters = array()) {
    foreach ($parameters as $key => $value) {
      $parameters[$key] = $key . '="' . rawurlencode($value) . '"';
    }
    if (!$this->_options->has('realm')) {
      array_unshift($parameters, 'realm="' . rawurlencode($this->_options->get('realm')) . '"');
    }
    return 'Authorization: OAuth ' . implode(', ', $parameters);
  }

  /********************************************************************************************************************/

  protected function _requestToken($url, array $parameters = array()) {
    return $this->_send($url, null, array(
      $this->_setupAuthorizationHeader($parameters)
    ), RequestInterface::METHOD_POST);
  }

  protected function _requestAccessToken($url, array $parameters = array()) {
    return $this->_send($url, null, array(
      $this->_setupAuthorizationHeader($parameters)
    ), RequestInterface::METHOD_POST);
  }

  protected function _requestUserInformation($url, array $parameters = array()) {
    return $this->_send($url, null, array(
      $this->_setupAuthorizationHeader($parameters)
    ), RequestInterface::METHOD_GET);
  }

}
