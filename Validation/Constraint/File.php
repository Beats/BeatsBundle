<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Context;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class File extends Constraint {

  public function __construct($failure = 'This image does not match the criteria, please select another one', $success = 'This Image is ok!') {
    parent::__construct($failure, $success);
  }

  public function validate($value, Context $context) {
    try {
      return ($value instanceof UploadedFile) && $value->isReadable();
    } catch (\Exception $ex) {
      return false;
    }
  }

}
