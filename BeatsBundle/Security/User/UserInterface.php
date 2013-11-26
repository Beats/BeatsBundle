<?php
namespace BeatsBundle\Security\User;

interface UserInterface {

  /**
   * @param int $kind
   * @return AuthInterface
   */
  public function getAuth($kind = 0);

  /**
   * @param int $kind
   * @return bool
   */
  public function hasAuth($kind = 0);

}