<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class LimitMin extends Limit {

  public function __construct($limit, $failure = 'The value is too small', $success = 'The value is valid') {
    parent::__construct($limit, $failure, $success);
  }

  protected function _isLegal($value, Context $context) {
    return $this->_limit <= $value;
  }

}