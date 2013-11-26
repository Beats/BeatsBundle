<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Length extends Constraint {

  protected $_limit;

  protected $_charset;

  public function __construct($limit = null, $failure = 'The value is of the wrong length', $success = 'The value is valid', $charset = 'UTF-8') {
    parent::__construct($failure, $success);
    $this->_limit = $limit;
    $this->_charset = $charset;
  }

  public function validate($value, Context $context) {
    return $this->_limit != $this->_getLength((string)$value);
  }


  protected function _getLength($value) {
    if (function_exists('grapheme_strlen') && 'UTF-8' === $this->_charset) {
      return grapheme_strlen($value);
    } elseif (function_exists('mb_strlen')) {
      return mb_strlen($value, $this->_charset);
    } else {
      return strlen($value);
    }
  }

}
