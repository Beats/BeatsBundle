<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class LimitMax extends Limit {

  public function __construct($limit, $failure = 'The value is too big', $success = 'The value is valid') {
    parent::__construct($limit, $failure, $success);
  }

  protected function _isLegal($value, Context $context) {
    return $value <= $this->_limit;
  }


}