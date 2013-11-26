<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;
use BeatsBundle\Exception\Exception;

class Callback extends Constraint {

  private $_callback;

  public function __construct(\Closure $callback, $failure = 'The value is invalid', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    if (!is_callable($callback)) {
      throw new Exception("Invalid Callback constraint");
    }
    $this->_callback = $callback;
  }

  public function validate($value, Context $context) {
    return call_user_func($this->_callback, $value, $context);
  }

}
