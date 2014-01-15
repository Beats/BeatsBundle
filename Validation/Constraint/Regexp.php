<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Exception\Exception;
use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Regexp extends Constraint {

  protected $_pattern;
  protected $_matches;

  public function __construct($pattern = '#.*#', $failure = 'The value is invalid', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    if (@preg_match($pattern, $pattern) === false) {
      throw new Exception("Invalid regular expression pattern [$pattern]");
    }
    $this->_pattern = $pattern;
  }

  public function validate($value, Context $context) {
    return preg_match($this->_pattern, $value, $this->_matches);
  }

  public function transform($value) {
    return parent::transform($value);
  }


}
