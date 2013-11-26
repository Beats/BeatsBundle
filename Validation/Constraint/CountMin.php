<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class CountMin extends Count {

  protected $_limit;

  public function __construct($limit = null, $failure = 'The value has too much elements', $success = 'The value is valid') {
    parent::__construct($limit, $failure, $success);
  }

  protected function _isLegal($value, Context $context) {
    return $this->_limit <= count($value);
  }

}
