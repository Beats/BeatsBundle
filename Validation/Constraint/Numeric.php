<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Numeric extends Constraint {

  public function __construct($failure = 'The value is not numeric', $success = 'The value is valid') {
    parent::__construct($failure, $success);
  }

  public function validate($value, Context $context) {
    return is_numeric($value);
  }

}