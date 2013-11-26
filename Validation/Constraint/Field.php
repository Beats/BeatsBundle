<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Field extends Constraint {

  protected $_field;
  protected $_strict;

  public function __construct($field, $strict = true, $failure = 'Values do not match', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    $this->_field = $field;
    $this->_strict = $strict;
  }


  public function validate($value, Context $context) {
    $copy = $context->getFields()->get($this->_field, null, true);
    return $this->_strict ? $value === $copy : $value == $copy;
  }

}
