<?php
namespace BeatsBundle\Validation\Constraint\Identity;

use BeatsBundle\Validation\Constraint;

interface ProviderInterface {

  /**
   * @param string $identity
   * @param $kind
   * @return boolean
   */
  public function exists($identity, $kind = null);

}
