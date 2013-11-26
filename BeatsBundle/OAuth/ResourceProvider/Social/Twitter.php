<?php

namespace BeatsBundle\OAuth\ResourceProvider\Social;

use BeatsBundle\OAuth\ResourceProvider\OAuth1;
use BeatsBundle\Security\User\Info\BaseInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Twitter extends OAuth1 {

  public function __construct(ContainerInterface $container, $name, array $options = array()) {
    parent::__construct($container, $name, array_merge(array(
      'authorize_url'       => 'https://api.twitter.com/oauth/authorize',
      'authorization_url'   => 'https://api.twitter.com/oauth/authenticate',
      'request_token_url'   => 'https://api.twitter.com/oauth/request_token',
      'access_token_url'    => 'https://api.twitter.com/oauth/access_token',
      'infos_url'           => 'http://api.twitter.com/1.1/account/verify_credentials.json',
      'realm'               => '',
      'signature_method'    => 'HMAC-SHA1',

      'access_token'        => '1415489096-HZfjvYOBWbK1UftLzSKqBDLnjsCgExn3Hig6hjY',
      'access_token_secret' => 'QcpobtJp4L7mQsEdaXDwhvpOvxbShTsZVyriRSuOGI',
    ), $options));
  }

  protected function _setupUserInformation($accessToken, array $data) {
    $data['verified'];
    list($nameF, $nameL) = explode(' ', $data['name']);
    $avatar = empty($data['default_profile_image']) ? null : $data['profile_image_url'];
    return new BaseInfo($accessToken, $this->getName(), $data,
      $data['id'], null, $data['screen_name'],
      $nameF, $nameL, null,
      $avatar
    );
  }


}