<?php
namespace BeatsBundle\Security\User;

interface AuthInterface {

  public function getUserID();

  public function generateCode();

}