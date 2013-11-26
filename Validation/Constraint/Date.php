<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Date extends Constraint {

  protected $_format;

  public function __construct($format = null, $failure = 'The value is not a valid date', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    $this->_format = $format;
  }

  public function validate($value, Context $context) {
    if (empty($this->_format)) {
      return strtotime($value) !== false;
    }
    $date = date_parse_from_format($this->_format, $value);
    return !empty($date) && empty($date['error_count']) && empty($data['warning_count']);
  }

}
