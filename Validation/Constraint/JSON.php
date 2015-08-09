<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class JSON extends Constraint {

  protected $_assoc;
  protected $_opts;
  protected $_depth;

  public function __construct(
    $assoc = false, $depth = 512, $opts = JSON_BIGINT_AS_STRING,
    $failure = 'The value is not a valid json string',
    $success = 'The value is a valid json string'
  ) {
    parent::__construct($failure, $success);
    $this->_assoc = $assoc;
    $this->_depth = $depth;
    $this->_opts  = $opts;
  }

  public function validate($value, Context $context) {
    $object = json_decode($value, $this->_assoc, $this->_depth, $this->_opts);
    if (json_last_error() !== JSON_ERROR_NONE) {
      return false;
    }
    $this->_transformed = $object;

    return true;
  }

}
