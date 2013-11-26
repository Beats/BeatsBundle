<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;

class Password extends Chain {

  public function __construct($minLength = 4, $strength = false,
                              $failureRequired = 'Password is required',
                              $failureLength = 'Password is too short',
                              $failureStrength = 'Password is to weak',
                              $success = 'Password is valid') {

    $constraints = array(
      new Required($failureRequired),
      new LengthMin($minLength, $failureLength),
    );
    if ($strength) {
      $constraints[] = new Password\Strength($failureStrength);
    }
    parent::__construct($constraints, false, false, false, null, $success);
  }

}
