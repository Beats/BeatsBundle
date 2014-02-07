<?php
namespace BeatsBundle\Validation;

class Validation {

  protected $_name;

  /**
   * @var Constraint;
   */
  protected $_constraint;

  /**
   * @var bool
   */
  protected $_success = false;

  /**
   * @var bool
   */
  protected $_optional = true;
  /**
   * @var bool
   */
  protected $_empty = true;

  /**
   * @var bool
   */
  protected $_executed;

  public function __construct($name, Constraint $constraint) {
    $this->_name       = $name;
    $this->_constraint = $constraint;
  }


  /**
   * @return Constraint
   */
  public function getConstraint() {
    return $this->_constraint;
  }

  /**
   * @return boolean
   */
  public function isExecuted() {
    return $this->_executed;
  }

  /**
   * @return boolean
   */
  public function isSuccess() {
    return $this->_success;
  }

  public function getName() {
    return $this->_name;
  }

  /**
   * @param boolean $success
   * @return $this
   */
  public function forceSuccess($success = false) {
    $this->_success = $success;
    return $this;
  }

  public function getValue() {
    return $this->_constraint->getTransformed();
  }

  /**
   * @return bool
   */
  public function isOptional() {
    return $this->_optional;
  }

  /**
   * @return bool
   */
  public function isEmpty() {
    return $this->_empty;
  }

  public function validate($value, Context $context) {
    $constraint = $this->getConstraint();
    if ($this->_executed) {
      if ($value === $constraint->getTransformed()) {
        return $this->_success;
      }
      $this->_executed = false;
    }
    $this->_success  = $constraint->execute($value, $context);
    $this->_optional = $constraint->isOptional();
    $value           = $constraint->getTransformed();
    $this->_empty    = $value === null || $value === '';
    $this->_executed = true;
    return $this->_success;
  }

  public function getMessage() {
    if ($this->isExecuted()) {
      if ($this->isSuccess()) {
        if ($this->isEmpty()) {
          $text = $this->getConstraint()->getMessageSuccess();
          $type = Message::TYPE_OPTIONAL;
        } else {
          $text = $this->getConstraint()->getMessageSuccess();
          $type = Message::TYPE_SUCCESS;
        }
      } else {
        $text = $this->getConstraint()->getMessageFailure();
        $type = Message::TYPE_FAILURE;
      }
      return new Message($this->getName(), $text, $type, $this->getValue());
    }
    throw new \BeatsBundle\Exception\Exception("The validation has not been executed yet");
  }

  public function setName($name) {
    $this->_name = $name;
  }

}
