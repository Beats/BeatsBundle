<?php

namespace BeatsBundle\Security\Http\Authentication;

use BeatsBundle\Service\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationHandler extends ContainerAware implements AuthenticationSuccessHandlerInterface {

  const SESSION_TARGET_URL = '_security.oauth.target_path';

  /**
   * @return HttpUtils
   */
  final protected function _httpUtils() {
    return $this->container->get('security.http_utils');
  }

  /********************************************************************************************************************/

  /**
   * @var string
   */
  protected $_providerKey;

  public function __construct(ContainerInterface $container, array $options) {
    parent::__construct($container, array_merge(array(
      'always_use_default_target_path' => false,
      'default_target_path'            => '/',
      'login_path'                     => '/login',
      'target_path_parameter'          => '_target_path',
      'use_referer'                    => false,
    ), $options));
  }

  /********************************************************************************************************************/

  /**
   * This is called when an interactive authentication attempt succeeds. This
   * is called by authentication listeners inheriting from
   * AbstractAuthenticationListener.
   *
   * @param Request $request
   * @param TokenInterface $token
   *
   * @return Response never null
   */
  public function onAuthenticationSuccess(Request $request, TokenInterface $token) {
    return $this->_httpUtils()->createRedirectResponse($request, $this->_determineTargetUrl($request));
  }

  /********************************************************************************************************************/

  /**
   * Get the provider key.
   *
   * @return string
   */

  public function getProviderKey() {
    return $this->_providerKey;
  }

  /**
   * Set the provider key.
   *
   * @param string $providerKey
   */
  public function setProviderKey($providerKey) {
    $this->_providerKey = $providerKey;
  }

  /**
   * Returns the handler options
   *
   * @return array
   */
  public function getOptions() {
    return $this->_options->all();
  }

  /********************************************************************************************************************/

  /**
   * Builds the target URL according to the defined options.
   *
   * @param Request $request
   *
   * @return string
   */
  protected function _determineTargetUrl(Request $request) {
    $options = $this->getOptions();

    if ($options['always_use_default_target_path']) {
      return $options['default_target_path'];
    }

    if ($targetUrl = $request->get($options['target_path_parameter'], null, true)) {
      return $targetUrl;
    }

    if ($targetUrl = $this->_fromSession(self::SESSION_TARGET_URL)) {
      return $targetUrl;
    }

    $providerKey = $this->getProviderKey();
    if (!empty($providerKey) && $this->_fromSession('_security.' . $providerKey . '.target_path')) {
      return $targetUrl;
    }

    if ($targetUrl = $this->_fromReferer($request)) {
      return $targetUrl;
    }

    return $options['default_target_path'];
  }

  protected function _fromSession($sessionKey) {
    $session = $this->_session();
    if ($targetUrl = $session->get($sessionKey, false)) {
      $session->remove($sessionKey);
    }
    return $targetUrl;
  }

  protected function _fromReferer(Request $request) {
    $options = $this->getOptions();
    if (!empty($options['use_referer'])) {
      $loginUrl = $this->_httpUtils()->generateUri($request, $options['login_path']);
      if (($targetUrl = $request->headers->get('Referer')) && $targetUrl !== $loginUrl) {
        return $targetUrl;
      }
    }
    return false;
  }


}
