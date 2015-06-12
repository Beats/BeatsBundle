<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Helper\UTC;
use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Date extends Constraint {

  protected $_format;
  protected $_zone;

  public function __construct($format = null, $zone = null, $failure = 'The value is not a valid date', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    $this->_format = $format;
  }

  protected function _dpiSetup() {
    $this->_zone = empty($zone) ? $this->_chronos()->getTimezone() : UTC::createTimeZone($zone);
  }

  public function validate($value, Context $context) {
    if (empty($this->_format)) {
      return strtotime($value) !== false;
    }
    $date = date_parse_from_format($this->_format, $value);
    if (!empty($date) && empty($date['error_count']) && empty($data['warning_count'])) {
      $dt = UTC::createDateTime($value, $this->_zone);
      if (empty($this->_format)) {
        $this->_transformed = UTC::toTimestamp($dt);
      } else {
        $this->_transformed = UTC::toFormat($this->_format, $dt);
      }

      return true;
    }

    return false;
  }

}
