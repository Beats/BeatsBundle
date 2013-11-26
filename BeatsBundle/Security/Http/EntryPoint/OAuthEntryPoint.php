<?php

namespace BeatsBundle\Security\Http\EntryPoint;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;

class OAuthEntryPoint implements AuthenticationEntryPointInterface {

  /**
   * @var HttpUtils
   */
  private $httpUtils;

  /**
   * @var string
   */
  private $loginPath;

  /**
   * Constructor
   *
   * @param HttpUtils              $httpUtils
   * @param string                 $loginPath
   */
  public function __construct(HttpUtils $httpUtils, $loginPath) {
    $this->httpUtils = $httpUtils;
    $this->loginPath = $loginPath;
  }

  public function start(Request $request, AuthenticationException $authException = null) {
    return $this->httpUtils->createRedirectResponse($request, $this->loginPath);
  }

}
