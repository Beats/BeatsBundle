<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class LengthMin extends Length {

  public function __construct($limit = null, $failure = 'The value is too short', $success = 'The value is valid', $charset = 'UTF-8') {
    parent::__construct($limit, $failure, $success, $charset);
  }

  public function validate($value, Context $context) {
    return $this->_limit <= $this->_getLength((string)$value);
  }

}
