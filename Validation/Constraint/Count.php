<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Count extends Traversable {

  protected $_limit;

  public function __construct($limit = null, $failure = 'The value does not have enough elements', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    $this->_limit = $limit | 0;
  }

  public function validate($value, Context $context) {
    return parent::validate($value, $context) && $this->_isLegal($value, $context);
  }

  protected function _isLegal($value, Context $context) {
    return $this->_limit != count($value);
  }

}
