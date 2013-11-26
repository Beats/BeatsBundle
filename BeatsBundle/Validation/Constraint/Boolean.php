<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Boolean extends Constraint {

  public function validate($value, Context $context) {
    return is_bool($value);
  }

  public function transform($value) {
    $value = parent::transform($value);
    if (empty($value)) {
      return false;
    } else if (is_numeric($value)) {
      return (bool)($value | 0);
    } elseif (is_string($value)) {
      return (bool)strcasecmp($value, 'false');
    }
    return true;
  }


}
