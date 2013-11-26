<?php

namespace BeatsBundle\Security\User;

use BeatsBundle\OAuth\ResourceProviderMap;
use BeatsBundle\Security\Core\Authentication\Token\OAuthToken;
use BeatsBundle\Security\Core\User\OAuthUserProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OAuthUserProvider extends UserProvider implements OAuthUserProviderInterface {

  const USER_CLASS = 'BeatsBundle\Security\Core\User\OAuthMember';

  /**
   * @return ResourceProviderMap
   */
  final protected function _oauthProviders() {
    return $this->container->get('beats.oauth.resource_provider.map');
  }

  static private $_default;

  public function __construct(ContainerInterface $container, $serviceID, $default = '') {
    parent::__construct($container, $serviceID);
    self::$_default = $default;
  }

  static public function _kind($name) {
    //TODO@ion: Remove static and parametrize
    static $kinds = array(
      'google'   => 1,
      'facebook' => 2,
      'windows'  => 3,
    );
    if (isset($kinds[$name])) {
      return $kinds[$name];
    }
    return 0;
  }

  static public function _provider($kind) {
    //TODO@ion: Remove static and parametrize
    static $providers = array(
      1 => 'Google',
      2 => 'Facebook',
      3 => 'Live',
    );
    if (isset($providers[$kind])) {
      return $providers[$kind];
    }
    return self::$_default;
  }

  /********************************************************************************************************************/

  public function loadUserByOAuthToken(OAuthToken $token) {

    $provider = $this->_oauthProviders()->byName($token->getProvider());

    $info = $provider->getUserInformation($token->getAccessToken());

    $md = $this->_persister();

    try {
      $kind = self::_kind($provider->getName());

      $auth = $md->findAuth($info->getID(), $kind, false);
      if (empty($auth)) {
        $user = $md->findUserByEmail($info->getEmail(), false);
        if (empty($user)) {
          $user = $md->signUpSocial($info, $kind);
        } else {
          $user = $md->attachSocial($user, $info, $kind);
        }
        $auth = $user->getAuth($kind);
      } else {
        $user = $md->findUserByID($auth->getUserID());
      }

    } catch (AuthenticationException $ex) {
      throw $ex;
    } catch (\Exception $ex) {
      throw new AuthenticationException(sprintf(
        "Error connecting external OAuth account<br/>\nProvider: %s<br/>\nUser: %s (%s)",
        $provider->getName(), $info->getEmail(), $info->getID()
      ));
    }
    return $md->buildMember($user, $auth, static::USER_CLASS);
  }

  /********************************************************************************************************************/

}
