<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Traversable extends Constraint {

  public function __construct($failure = 'The value is not a collection', $success = 'The value is valid') {
    parent::__construct($failure, $success);
  }

  public function validate($value, Context $context) {
    return is_array($value) || $value instanceof \Traversable;
  }

  public function transform($value) {
    if (is_null($value)) {
      return array();
    }
    return $value;
  }

}
