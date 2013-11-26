<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;

class MultiID extends Chain {


  public function __construct($min, $max, $failureMin = 'The value has too few elements', $failureMax = 'The value has too much elements', $success = 'The value is valid') {
    parent::__construct(array(
      new CountMin($min, $failureMin),
      new CountMax($max, $failureMax),
    ), false, false, false, null, $success);
  }


  public function transform($value) {
    return array_unique(array_filter(array_map(function ($item) {
      return intval($item);
    }, $value)));
  }

}
