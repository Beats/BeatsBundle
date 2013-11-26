<?php

namespace BeatsBundle\OAuth\ResourceProvider\Social;

use BeatsBundle\OAuth\ResourceProvider\OAuth2;
use BeatsBundle\Security\User\Info\BaseInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class Google extends OAuth2 {

  public function __construct(ContainerInterface $container, $name, array $options = array()) {
    parent::__construct($container, $name, array_merge(array(
      'authorization_url' => 'https://accounts.google.com/o/oauth2/auth',
      'access_token_url'  => 'https://accounts.google.com/o/oauth2/token',
      'infos_url'         => 'https://www.googleapis.com/oauth2/v2/userinfo',
      'scope'             => 'openid email profile',
    ), $options));
  }

  protected function _setupUserInformation($accessToken, array $data) {
    if (empty($data['verified_email'])) {
      throw new AuthenticationException('Account not verified');
    }
    $id       = $data['id'];
    $email    = $data['email'];
    $username = BaseInfo::toUsername($email, $data);

    list($nameF, $nameL) = BaseInfo::toNames($username, $data, 'name', 'given_name', 'family_name');
    $dob = (empty($data['birthday']) || preg_match('#^0000-#', $data['birthday'])) ? null : $data['birthday'];

    $avatar   = empty($data['picture']) ? null : $data['picture'];
    $locale   = empty($data['locale']) ? null : $data['locale'];
    $timezone = empty($data['timezone']) ? null : $data['timezone'];

    return new BaseInfo($accessToken, $this->getName(), $data,
      $id, $email, $username,
      $nameF, $nameL, $dob,
      $avatar, $locale, $timezone
    );
  }

}
