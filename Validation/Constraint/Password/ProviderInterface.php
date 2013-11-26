<?php
namespace BeatsBundle\Validation\Constraint\Password;

use BeatsBundle\Validation\Constraint;

interface ProviderInterface {

  /**
   * @param string $identity
   * @param $kind
   * @return string|null
   */
  public function password($identity, $kind = null);

}
