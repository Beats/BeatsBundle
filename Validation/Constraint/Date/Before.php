<?php
namespace BeatsBundle\Validation\Constraint\Date;

use BeatsBundle\Helper\UTC;
use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;


class Before extends Constraint {

  /**
   * @var \DateTime
   */
  protected $_time;
  /**
   * @var \DateTimeZone
   */
  protected $_zone;

  public function __construct($time = null, $zone = 'UTC', $failure = 'The provided date is invalid', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    $this->_zone = UTC::createTimeZone($zone);
    $this->_time = UTC::createDateTime($time, $this->_zone);
  }

  public function validate($value, Context $context) {
    return UTC::createDateTime($value, $this->_zone) < $this->_time;
  }

}
