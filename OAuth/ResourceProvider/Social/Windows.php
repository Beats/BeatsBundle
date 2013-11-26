<?php

namespace BeatsBundle\OAuth\ResourceProvider\Social;

use BeatsBundle\OAuth\ResourceProvider\OAuth2;
use BeatsBundle\Security\User\Info\BaseInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class Windows extends OAuth2 {

  public function __construct(ContainerInterface $container, $name, array $options = array()) {
    parent::__construct($container, $name, array_merge(array(
      'authorization_url' => 'https://login.live.com/oauth20_authorize.srf',
      'access_token_url'  => 'https://login.live.com/oauth20_token.srf',
      'infos_url'         => 'https://apis.live.net/v5.0/me',
      'scope'             => 'wl.basic wl.signin wl.birthday wl.emails',
    ), $options));
  }

  protected function _setupUserInformation($accessToken, array $data) {
    $emails = empty($data['emails']) ? array() : array_filter($data['emails']);
    if (empty($emails)) {
      throw new AuthenticationException('Account not verified');
    }
    $id       = $data['id'];
    $email    = empty($emails['account']) ? reset($emails) : $emails['account'];
    $username = BaseInfo::toUsername($email, $data);

    list($nameF, $nameL) = BaseInfo::toNames($username, $data, 'name', 'first_name', 'last_name');
    $dob = (empty($data['birth_year']) || empty($data['birth_month']) || empty($data['birth_day']))
      ? null
      : implode('-', array($data['birth_year'], $data['birth_month'], $data['birth_day']));

    $avatar   = "https://apis.live.net/v5.0/$id/picture";
    $locale   = empty($data['locale']) ? null : $data['locale'];
    $timezone = empty($data['timezone']) ? null : $data['timezone'];

    return new BaseInfo($accessToken, $this->getName(), $data,
      $id, $email, $username,
      $nameF, $nameL, $dob,
      $avatar, $locale, $timezone
    );
  }

}
