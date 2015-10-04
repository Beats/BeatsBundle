<?php
namespace BeatsBundle\Security\User;

interface AuthInterface {

  const KIND_DIRECT = 'direct';

  public function getUserID();

  public function generateCode();

  public function isKind($kind = self::KIND_DIRECT);

  //public function getKind();

}