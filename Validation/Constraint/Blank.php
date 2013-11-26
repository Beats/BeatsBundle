<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Blank extends Constraint {

  public function validate($value, Context $context) {
    return empty($value);
  }

}
