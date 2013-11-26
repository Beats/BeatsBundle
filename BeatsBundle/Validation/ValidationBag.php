<?php
namespace BeatsBundle\Validation;

use Symfony\Component\HttpFoundation\ParameterBag;


class ValidationBag extends ParameterBag {

  public function __construct(array $parameters = array()) {
    $this->parameters = array();
    foreach ($parameters as $field => $constraint) {
      if ($constraint instanceof Constraint) {
        $validation = new Validation($field, $constraint);
      } elseif ($constraint instanceof Validation) {
        $validation = $constraint;
      } else {
        throw new \BeatsBundle\Exception\Exception("Invalid constraint or validation for [$field]");
      }
      $this->set($field, $validation);
    }
  }

}
