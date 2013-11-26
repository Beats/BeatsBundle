<?php
namespace BeatsBundle\Validation\Constraint\File;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Mime extends Constraint {

  protected $_mime;

  public function __construct($mime, $failure = 'The uploaded file is not of the valid type', $success = 'The value is valid') {
    parent::__construct($failure, $success);
    if (empty($mime)) {
      $mime = '#.*#';
    } else {
      if (preg_match($mime, $mime) === false) {
        throw new \BeatsBundle\Exception\Exception("The given mime constraint is not a valid regexp [$mime]");
      }
    }
    $this->_mime = $mime;
  }

  public function validate($value, Context $context) {
    try {
      return ($value instanceof UploadedFile) && preg_match($this->_mime, $value->getMimeType());
    } catch (\Exception $ex) {
      return false;
    }
  }

}
