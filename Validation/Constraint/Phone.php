<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Phone extends Constraint {

  protected $_format;

  public function __construct($format = null, $failure = 'The value is not a valid phone', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    $this->_format = $format;
  }

  public function validate($value, Context $context) {
    if (empty($this->_format)) {
      return preg_match('#\d{6,}#', preg_replace('#[^\d]#', '', $value));
    }
    // LATER: Create a better phone number validation
    $context->getFields()->get('country');
    return true;
  }

  public function transform($value) {
    // LATER: Transform the input string into a better format
    return parent::transform($value);
  }

}
