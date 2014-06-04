<?php

namespace BeatsBundle\OAuth;

use BeatsBundle\Service\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\HttpUtils;

class ResourceProviderMap extends ContainerAware {

  /********************************************************************************************************************/

  /**
   * @var HttpUtils
   */
  protected $_httpUtils;

  /********************************************************************************************************************/

  /**
   *
   * @var ParameterBag
   */
  protected $_configs;

  /**
   *
   * @var ParameterBag
   */
  protected $_providers;

  /**
   * @var array
   */
  protected $_names;

  /**
   * @var string
   */
  protected $_callbackURI;

  /********************************************************************************************************************/

  public function __construct(ContainerInterface $container, HttpUtils $httpUtils, array $providers = array(), $callbackURI = null) {
    parent::__construct($container);
    $this->_httpUtils   = $httpUtils;
    $this->_configs     = new FrozenParameterBag($providers);
    $this->_providers   = new ParameterBag(array());
    $this->_callbackURI = $callbackURI;
    $this->_names       = array();
    foreach ($providers as $kind => $data) {
      $this->_names[$kind] = $this->_trans('beats.oauth.providers.' . $kind);
    }
  }


  protected function _createProvider($name) {
    if (!$this->_configs->has($name)) {
      throw new AuthenticationException("OAuth resource provider not found: $name");
    }
    $config = $this->_configs->get($name);

    $class = empty($config['class'])
      ? 'BeatsBundle\OAuth\ResourceProvider\Social\\' . ucfirst($name)
      : $config['class'];
    try {
      $provider = new $class($this->container, $name, $config);
    } catch (\Exception $ex) {
      throw new AuthenticationException("Couldn't instantiate OAuth resource provider: $name", $ex->getMessage(), 0, $ex);
    }
    if ($provider instanceof ResourceProviderInterface) {
      $this->_providers->set($name, $provider);
      return $provider;
    }
    throw new AuthenticationException("Invalid OAuth resource provider");
  }

  /********************************************************************************************************************/

  /**
   * @return array
   */
  public function getProviders() {
    return $this->_names;
  }

  /**
   * Gets the appropriate resource provider given the name.
   *
   * @param string $name
   *
   * @return ResourceProviderInterface
   */
  public function byName($name) {
    if ($this->_providers->has($name)) {
      return $this->_providers->get($name);
    }
    return $this->_createProvider($name);
  }

  /**
   * Gets the appropriate resource provider for a request.
   *
   * @param Request $request
   *
   * @return ResourceProviderInterface
   * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
   */
  public function byRequest(Request $request) {
    $provider = $request->get('provider');
    if (empty($provider)) {
      throw new AuthenticationException("Invalid OAuth callback redirect uri. Provider name missing.");
    }
    return $this->byName($provider);
  }

  public function requiresAuthentication(Request $request) {
    if (empty($this->_callbackURI)) {
      foreach ($this->getProviders() as $name) {
        if ($this->_httpUtils->checkRequestPath($request, $this->byName($name)->getCheckPath())) {
          return true;
        }
      }
      return false;
    }
    return $this->_httpUtils->checkRequestPath($request, $this->_callbackURI);
  }

  /********************************************************************************************************************/

}
