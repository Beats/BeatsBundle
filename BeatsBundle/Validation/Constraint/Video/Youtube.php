<?php
namespace BeatsBundle\Validation\Constraint\Video;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;
use BeatsBundle\Validation\Constraint\Regexp;

class Youtube extends Regexp {

  public function __construct($failure = 'The this value is not Youtube video address', $success = 'The value is valid') {
    parent::__construct('/.*(?:youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=)([^#\&\?\"]*).*/', $failure, $success);
  }

  public function validate($value, Context $context) {
    return is_string($value);
  }

  public function transform($value) {
    if (preg_match($this->_pattern, parent::transform($value), $matches)) {
      return 'http://www.youtube.com/embed/'.$matches[1];
    };
    return true;
  }

}