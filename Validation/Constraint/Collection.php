<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Collection extends Traversable {

  /**
   * @var Constraint
   */
  private $_constraint;

  public function __construct(Constraint $constraint, $failure = 'The value is not a collection', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    $this->_constraint = $constraint;
  }

  public function validate($value, Context $context) {
    if (parent::validate($value, $context)) {
      $success = $this->_optional;
      foreach($value as $sub) {
        $success = $this->_constraint->validate($sub, $context) && $success;
      }
      return $success;
    }
    return false;
  }

  public function transform($value) {
    return array_filter($value);
  }

}
