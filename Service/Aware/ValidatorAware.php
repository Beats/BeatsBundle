<?php
namespace BeatsBundle\Service\Aware;

use BeatsBundle\Validation\Validator;

trait ValidatorAware {

  /**
   * @return Validator
   */
  final protected function _validator() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('beats.validation.validator');
  }

}






