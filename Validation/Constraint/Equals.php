<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Equals extends Constraint {

  protected $_value;
  protected $_strict;

  public function __construct($value, $strict = true, $failure = 'Values do not match', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    $this->_value = $value;
    $this->_strict = $strict;
  }


  public function validate($value, Context $context) {
    return $this->_strict ? $value === $this->_value : $value == $this->_value;
  }

}
