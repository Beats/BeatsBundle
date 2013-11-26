<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class None extends Constraint {

  public function validate($value, Context $context) {
    return true;
  }

}
