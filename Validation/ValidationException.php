<?php
namespace BeatsBundle\Validation;

use BeatsBundle\Exception\Exception;
use Symfony\Component\HttpFoundation\ParameterBag;

class ValidationException extends Exception {

  /**
   * @var Context
   */
  protected $_context;

  protected $_step;

  public function __construct(Context $context, $step = -1) {
    parent::__construct(implode("\n", $context->getErrors()->flat()));
    $this->_context = $context;
    $this->_step    = $step;
  }


  /**
   * @return ParameterBag
   */
  public function getValids() {
    return $this->_context->getValids();
  }

  /**
   * @return ParameterBag
   */
  public function getErrors() {
    return $this->_context->getErrors();
  }

  /**
   * @return ParameterBag
   */
  public function getMessages() {
    return $this->_context->getMessages();
  }

  /**
   * @return Context
   */
  public function getContext() {
    return $this->_context;
  }

  public function setStep($step) {
    $this->_step = $step;
    return $this;
  }

  public function getStep() {
    return $this->_step;
  }

  public function hasStep() {
    return -1 < $this->_step;
  }

  public function setupStep(&$step) {
    if ($this->_step < 0) {
      $step += $this->_step;
    } else {
      $step = $this->_step;
    }
  }


}
