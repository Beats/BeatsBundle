<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Float extends Constraint {

  public function __construct($limit = null, $failure = 'The value is not float', $success = 'The value is valid', $charset = 'UTF-8') {
    parent::__construct($failure, $success);
  }

  public function validate($value, Context $context) {
    return is_float($value);
  }

  public function transform($value) {
    return floatval($value);
  }

}
