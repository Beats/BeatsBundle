<?php

namespace BeatsBundle\OAuth\ResourceProvider;

use BeatsBundle\OAuth\ResourceProviderInterface;
use BeatsBundle\Security\User\InfoInterface;
use BeatsBundle\Service\ContainerAware;
use Buzz\Browser;
use Buzz\Exception\ClientException;
use Buzz\Message;
use Buzz\Message\RequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\HttpUtils;

abstract class AbstractResourceProvider extends ContainerAware implements ResourceProviderInterface {

  /**
   * @var string
   */
  protected $_name;

  protected $_checkPath;

  private $_errors = array(
    'redirect_uri_mismatch'        => 'Redirect URI mismatches configured one.',
    'bad_verification_code'        => 'Bad verification code.',
    'incorrect_client_credentials' => 'Incorrect client credentials.',
    'unauthorized_client'          => 'Unauthorized client.',
    'invalid_assertion'            => 'Invalid assertion.',
    'unknown_format'               => 'Unknown format.',
    'authorization_expired'        => 'Authorization expired.',
    'access_denied'                => 'You have refused access for this site.',
    'unsupported_request'          => 'Redirect request not supported.',
  );

  /********************************************************************************************************************/

  /**
   * @return HttpUtils
   */
  final protected function _httpUtils() {
    return $this->container->get('security.http_utils');
  }

  /**
   * @return Browser
   */
  final  protected function _httpClient() {
    return $this->container->get('buzz.browser');
  }

  /********************************************************************************************************************/

  public function __construct(ContainerInterface $container, $name, array $options = array()) {
    parent::__construct($container, array_merge(array(
      'client_id'         => '',
      'client_secret'     => '',
      'authorization_url' => '',
      'access_token_url'  => '',
      'infos_url'         => '',
      'scope'             => '',
      'callback'          => '',
    ), $options));
    $this->_name = $name;
  }

  /********************************************************************************************************************/

  public function getName() {
    return $this->_name;
  }

  public function getClientID() {
    return $this->_options->get('client_id');
  }

  public function getCheckPath() {
    if (empty($this->_checkPath)) {
      $this->_checkPath = $this->_buildRedirectURL($this->_options->get('callback'));
    }
    return $this->_checkPath;
  }

  public function getScope() {
    return $this->_options->get('scope');
  }

  protected function _getClientSecret() {
    return $this->_options->get('client_secret');
  }

  /********************************************************************************************************************/

  private function _buildRedirectURL($callback) {
    if ('/' === $callback[0]) { // Relative URL
      $url = $this->_request()->getSchemeAndHttpHost() . $callback;
    } elseif (preg_match('#^http#', $callback)) { // Absolute URL
      $url = $callback;
    } else { // Route name
      $url = $this->_router()->generate($callback, array(
        'provider' => $this->getName(),
      ), true);
    }
    $parameters = array();
    if ($this->_kernel()->isDebug()) {
      $parameters['XDEBUG_SESSION'] = 'PHPSTORM';
    }
    if (empty($parameters)) {
      return $url;
    }
    return implode(strpos($url, '?') === false ? '?' : '&', array($url, http_build_query($parameters)));
  }

  protected function _url($name, array $parameters = array()) {
    $url = $this->_options->get($name);
    if (empty($parameters)) {
      return $url;
    }
    return implode(false === strpos($url, '?') ? '?' : '&', array(
      $url, http_build_query($parameters)
    ));
  }

  protected function _urlUserInfo(array $parameters = array()) {
    return $this->_url('infos_url', $parameters);
  }

  protected function _urlAuthorization(array $parameters = array()) {
    return $this->_url('authorization_url', $parameters);

  }

  protected function _urlAccessToken(array $parameters = array()) {
    return $this->_url('access_token_url', $parameters);
  }

  /********************************************************************************************************************/

  /**
   *
   * Performs an HTTP request
   *
   * @param string $url The url to fetch
   * @param string $content The content of the request
   * @param array $headers The headers of the request
   * @param string $method The HTTP method to use
   *
   * @return Message\Response
   * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
   */
  protected function _send($url, $content = null, $headers = array(), $method = null) {
    if (is_null($method)) {
      $method = is_null($content) ? RequestInterface::METHOD_GET : RequestInterface::METHOD_POST;
    }

    $request  = new Message\Request($method, $url);
    $response = new Message\Response();

    $headers = array_merge(array(
      'User-Agent' => self::USER_AGENT,
    ), $headers);

    $request->setHeaders($headers);

    $request->setContent($content);

    try {
      $this->_httpClient()->getClient()->setTimeout(10);
      $this->_httpClient()->send($request, $response);
    } catch (ClientException $ex) {
      throw new AuthenticationException($ex->getMessage(), 'Communication Exception', 0, $ex);
    }

    return $response;
  }

  /**
   * Get the 'parsed' content based on the response headers.
   *
   * @param Message\MessageInterface $rawResponse
   *
   * @return array
   *
   * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
   */
  protected function _parseResponse(Message\MessageInterface $rawResponse) {
    if (false !== strpos($rawResponse->getHeader('Content-Type'), 'application/json')) {
      $response = json_decode($rawResponse->getContent(), true);

      if (JSON_ERROR_NONE !== json_last_error()) {
        throw new AuthenticationException(sprintf('Not a valid JSON response.'));
      }

    } else {
      parse_str($rawResponse->getContent(), $response);
    }
    return $response;
  }

  /**
   * @param string $url
   * @param array $parameters
   *
   * @return string
   */
  abstract protected function _requestAccessToken($url, array $parameters = array());

  /**
   * @param string $url
   * @param array $parameters
   *
   * @return mixed
   */
  abstract protected function _requestUserInformation($url, array $parameters = array());

  /**
   * Parses the user info response into a InfoInterface object
   * @param $accessToken
   * @param array $data
   * @return InfoInterface
   */
  abstract protected function _setupUserInformation($accessToken, array $data);

  /********************************************************************************************************************/

  /**
   * @param Request $request
   * @return $this|ResourceProviderInterface
   * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
   */
  public function validate(Request $request) {
    if ($request->query->has('error')) {
      $code = $request->query->get('error');
      $text = $request->query->get('message');
    } else {
      $content = json_decode($request->getContent(), true);
      if (json_last_error()) {
        $code = "invalid_request_format";
        $text = "The OAuth request format is invalid";
      } else {
        if (isset($content['error'])) {

          $error = $content['error'];

          if (isset($error['code'])) {
            $code = $error['code'];
          } elseif (isset($error['error-code'])) {
            $code = $error['error-code'];
          } else {
            $code = 'error_code_missing';
          }

          if (isset($error['message'])) {
            $text = $error['message'];
          }
        } else {
          if ($this->supports($request)) {
            return $this; // The Request is error free and supported
          }
          $text = "OAuth callback request not supported: {$this->getName()}";
          $code = 'unsupported_request';
        }
      }
    }

    if (empty($text)) {
      $text = isset($this->_errors[$code]) ? $this->_errors[$code] : "OAuth error: $code";
    }
    throw new AuthenticationException($text, $code);
  }

  /********************************************************************************************************************/


  public function urlConnect() {
    return $this->getAuthorizationUrl($this->getCheckPath());
  }

  /********************************************************************************************************************/

}
