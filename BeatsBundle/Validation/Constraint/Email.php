<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Email extends Constraint {

  public function __construct($failure = 'The value is not a valid email', $success = 'The value is a valid email') {
    parent::__construct($failure, $success);
  }

  public function validate($value, Context $context) {
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
  }

  public function transform($value) {
    return parent::transform(is_string($value) ? strtolower($value) : $value);
  }


}
