<?php
namespace BeatsBundle\Validation\Constraint\File;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class MaxSize extends Constraint {

  static private $_units = array(
    'B'  => 0,
    'KB' => 1,
    'MB' => 2,
    'GB' => 3,
    'TB' => 4,
    'PB' => 5,
    'EB' => 6,
    'ZB' => 7,
    'YB' => 8,
  );

  protected $_maxSize;

  public function __construct($maxSize, $failure = 'The file is too big', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    if (is_string($maxSize)) {
      if (!preg_match('#(\d+)\s*([KMGTPEZY]?B)#', $maxSize, $matches)) {
        throw new \BeatsBundle\Exception\Exception("Invalid maxSize [$maxSize]");
      }
      $this->_maxSize = $matches[1] * pow(1024, self::$_units[$matches[2]]);
    } else {
      $this->_maxSize = abs($maxSize);
    }
  }

  public function validate($value, Context $context) {
    try {
      return ($value instanceof UploadedFile) && $this->_maxSize && ($value->getSize() < $this->_maxSize);
    } catch (\Exception $ex) {
      return false;
    }
  }

}
