<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;

class Username extends Regexp {

  public function __construct($failure = 'The value is not a valid username', $success = 'The value is a valid username') {
    parent::__construct('#^[a-z][\w.]{2,}$#i', $failure, $success);
  }

}
