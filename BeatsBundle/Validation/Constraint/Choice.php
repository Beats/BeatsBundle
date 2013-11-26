<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Choice extends Constraint {

  /**
   * @var array
   */
  protected $_choices;

  public function __construct(array $choices, $failure = 'The value is invalid', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    $this->_choices = $choices;
  }

  public function validate($value, Context $context) {
    return empty($this->_choices) || isset($this->_choices[$value]) || in_array($value, $this->_choices);
  }

}
