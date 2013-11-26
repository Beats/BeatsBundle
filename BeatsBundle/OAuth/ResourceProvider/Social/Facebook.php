<?php

namespace BeatsBundle\OAuth\ResourceProvider\Social;

use BeatsBundle\Helper\UTC;
use BeatsBundle\Helper\UTCException;
use BeatsBundle\OAuth\ResourceProvider\OAuth2;
use BeatsBundle\Security\User\Info\BaseInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class Facebook extends OAuth2 {

  public function __construct(ContainerInterface $container, $name, array $options = array()) {
    $options = array_merge(array(
      'authorization_url' => 'https://www.facebook.com/dialog/oauth',
      'access_token_url'  => 'https://graph.facebook.com/oauth/access_token',
      'infos_url'         => 'https://graph.facebook.com/me',
      'scope'             => 'email user_birthday',
    ), $options);
    parent::__construct($container, $name, $options);
  }

  protected function _setupUserInformation($accessToken, array $data) {
    if (empty($data['verified'])) {
      throw new AuthenticationException('Account not verified');
    }
    $id       = $data['id'];
    $email    = $data['email'];
    $username = BaseInfo::toUsername($email, $data, 'username');

    list($nameF, $nameL) = BaseInfo::toNames($username, $data, 'name', 'first_name', 'last_name');
    $dob = empty($data['birthday']) ? null : $data['birthday'];

    $avatar = "http://graph.facebook.com/$username/picture?type=large";
    $locale = empty($data['locale']) ? null : $data['locale'];

    try {
      $timezone = isset($data['timezone']) ? UTC::normalizeTimeZone($data['timezone']) : null;
    } catch (UTCException $ex) {
      $timezone = null;
      $this->_logger()->error($ex->getMessage());
    }

    return new BaseInfo($accessToken, $this->getName(), $data,
      $id, $email, $username,
      $nameF, $nameL, $dob,
      $avatar, $locale, $timezone
    );
  }

}
