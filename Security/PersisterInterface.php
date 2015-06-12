<?php
namespace BeatsBundle\Security;

use BeatsBundle\Security\User\AuthInterface;
use BeatsBundle\Security\User\InfoInterface;
use BeatsBundle\Security\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

interface PersisterInterface {

  /**
   * @param string $identity
   * @param int    $kind
   * @param bool   $throw
   * @return AuthInterface|null
   * @throws UsernameNotFoundException
   */
  public function findAuth($identity, $kind = 0, $throw = true);

  /**
   * @param      $userID
   * @param bool $throw
   * @return UserInterface|null
   * @throws UnsupportedUserException
   */
  public function findUserByID($userID, $throw = true);

  /**
   * @param      $identity
   * @param bool $throw
   * @return UserInterface|null
   * @throws UnsupportedUserException
   */
  public function findUserByIdentity($identity, $throw = true);

  /**
   * @param UserInterface $user
   * @param AuthInterface $auth
   * @param               $class
   * @return \Symfony\Component\Security\Core\User\UserInterface
   */
  public function buildMember(UserInterface $user, AuthInterface $auth, $class);

  /**
   * @param InfoInterface $info
   * @param AuthInterface $auth
   * @param               $kind
   * @return UserInterface
   */
  public function signUpDirect(InfoInterface $info, AuthInterface $auth, $kind = 0);

  /**
   * @param UserInterface $user
   * @param InfoInterface $info
   * @param AuthInterface $auth
   * @param               $kind
   * @return UserInterface
   */
  public function attachDirect(UserInterface $user, InfoInterface $info, AuthInterface $auth, $kind = 0);

  /**
   * @param InfoInterface $info
   * @param               $kind
   * @return UserInterface
   */
  public function signUpSocial(InfoInterface $info, $kind);

  /**
   * @param UserInterface $user
   * @param InfoInterface $info
   * @param               $kind
   * @return UserInterface
   */
  public function attachSocial(UserInterface $user, InfoInterface $info, $kind);

  /**
   * @param Request        $request
   * @param TokenInterface $token
   * @param Response       $response
   * @return Response
   */
  public function onAuthenticationSuccess(Request $request, TokenInterface $token = null, Response $response = null);

}