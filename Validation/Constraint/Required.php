<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Required extends Constraint {

  public function __construct($failure = 'Required field', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    $this->_optional = false;
  }


  public function validate($value, Context $context) {
    return !$this->isEmpty();
  }

}
