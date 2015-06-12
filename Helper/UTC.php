<?php
namespace BeatsBundle\Helper;

class UTC {

  /**
   * @param mixed|null $zone
   * @return \DateTimeZone
   * @throws UTCException
   */
  static public function createTimeZone($zone = 'UTC') {
    if ($zone instanceof \DateTimeZone) {
      return $zone;
    } elseif (is_numeric($zone)) { // GMT offset in hours, with DST in affect
      $zone = timezone_name_from_abbr('', $zone * 3600, 1);
      if (empty($zone)) {
        throw new UTCException("Invalid timezone offset: $zone");
      }
    }
    try {
      return new \DateTimeZone($zone);
    } catch (\Exception $ex) {
      throw new UTCException("Invalid timezone format: $zone", 0, $ex);
    }
  }

  /**
   * Creates a \DateTime object based on given value;
   *
   * If the zone is not set, the value will be parsed as an UTC time.
   *
   * @param mixed|null $time
   * @param mixed|null $zone
   * @return \DateTime
   * @throws UTCException
   */
  static public function createDateTime($time = null, $zone = null) {
    static $utc;
    if (empty($utc)) {
      $utc = self::createTimeZone();
    }
    if (empty($zone)) {
      $zone = $utc;
    } else {
      $zone = self::createTimeZone($zone);
    }
    if ($time instanceof \DateTime) {
      return $time->setTimezone($zone);
    } elseif (is_null($time)) {
      $time = 'now';
    } elseif (is_numeric($time)) {
      $time = '@' . $time;
    } elseif (!is_string($time)) {
      throw new UTCException("Invalid time format: " . gettype($time));
    }
    try {
      return new \DateTime($time, $zone);
    } catch (\Exception $ex) {
      throw new UTCException("Invalid time format: $time", 0, $ex);
    }
  }

  /**
   * Created a string representation of the given time according to the specified format
   *
   * @param \DateTime $time
   * @param string $format
   * @return string
   * @throws UTCException
   */
  static private function _format(\DateTime $time, $format) {
    $text = $time->format($format);
    if (empty($text)) {
      throw new UTCException("Invalid format given: $format");
    }
    return $text;
  }

  /********************************************************************************************************************/

  /**
   * Normalize the timezone parameter to a PHP friendly string
   *
   * @param mixed|null $zone
   * @return string
   * @param mixed|null $zone
   */
  static public function normalizeTimeZone($zone) {
    return self::createTimeZone($zone)->getName();
  }

  /********************************************************************************************************************/

  /**
   * Returns the given time as a UNIX timestamp
   * @param mixed|null $time
   * @return int
   * @throws UTCException
   */
  static public function toUNIX($time = null) {
    return self::createDateTime($time)->getTimestamp();
  }

  /**
   * Returns the given time formatted according to the parameters
   * @param $format
   * @param null $time
   * @return string
   * @throws UTCException
   */
  static public function toFormat($format, $time = null) {
    return self::_format(self::createDateTime($time), $format);
  }

  /********************************************************************************************************************/

  /**
   * Returns the date and time portion of the given time in the format YYYY-MM-DD hh:mm:ss
   * @param mixed|null $time
   * @return string
   * @throws UTCException
   */
  static public function toTimestamp($time = null) {
    return self::toFormat('Y-m-d H:i:s', $time);
  }

  /**
   * Returns the date and time portion of the given time in ISO 8601 format YYYY-MM-DD hh:mm:ss
   * @param mixed|null $time
   * @return string
   * @throws UTCException
   */
  static public function toFull($time = null) {
    return self::toFormat('Y-m-d H:i:s', $time);
  }

  /**
   * Returns the date and time portion of the given time in the format YYYY-MM-DD hh:mm
   * @param mixed|null $time
   * @return string
   * @throws UTCException
   */
  static public function toMinutes($time = null) {
    return self::toFormat('Y-m-d H:i', $time);
  }

  /**
   * Returns the date and time portion of the given time in the format YYYY-MM-DD hh
   * @param mixed|null $time
   * @return string
   * @throws UTCException
   */
  static public function toHours($time = null) {
    return self::toFormat('Y-m-d H', $time);
  }

  /**
   * Returns the date portion of the given time in ISO 8601 format YYYY-MM-DD
   * @param mixed|null $time
   * @return string
   * @throws UTCException
   */
  static public function toDate($time = null) {
    return self::toFormat('Y-m-d', $time);
  }

  /**
   * Returns the date portion of the given time in the format YYYY-MM
   * @param mixed|null $time
   * @return string
   * @throws UTCException
   */
  static public function toMonths($time = null) {
    return self::toFormat('Y-m', $time);
  }

  /**
   * Returns the date portion of the given time in the format YYYY
   * @param mixed|null $time
   * @return string
   * @throws UTCException
   */
  static public function toYears($time = null) {
    return self::toFormat('Y', $time);
  }

  /********************************************************************************************************************/

  /**
   * Returns the time portion of the given time in ISO 8601 format hh:mm:ss
   * @param mixed|null $time
   * @return string
   * @throws UTCException
   */
  static public function toTime($time = null) {
    return self::toFormat('H:i:s', $time);
  }

  /********************************************************************************************************************/

  /**
   * Returns a string representation of the given time, adjusted to the given timezone, by the specified format
   *
   * @param string $format
   * @param mixed $zone
   * @param mixed|null $time
   * @return string
   * @throws UTCException
   */
  static public function toTZFormat($format, $zone, $time = null) {
    if (empty($zone)) {
      throw new UTCException("Timezone not specified");
    }
    return self::_format(self::createDateTime($time)->setTimezone(self::createTimeZone($zone)), $format);
  }

  /********************************************************************************************************************/

  /**
   * @param string $iso
   * @return string
   */
  static public function partDate($iso) {
    return substr($iso, 0, 10);
  }

}
