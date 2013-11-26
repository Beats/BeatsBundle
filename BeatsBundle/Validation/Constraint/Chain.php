<?php
namespace BeatsBundle\Validation\Constraint;

use BeatsBundle\Validation\Constraint;
use BeatsBundle\Validation\Context;

class Chain extends Constraint {

  /**
   * @var Traversable
   */
  protected $_constraints;

  /**
   * @var boolean
   */
  protected $_strict;

  /**
   * @var boolean
   */
  private $_isOR;


  public function __construct($constraints, $toNULL = false, $strict = false, $isOR = false, $failure = null, $success = null) {
    parent::__construct($failure, $success);
    $this->_constraints = is_array($constraints) ? new \ArrayIterator($constraints) : $constraints;
    $this->_strict      = $strict;
    $this->_isOR        = $isOR;
    $this->_toNULL      = $toNULL;
    $this->_optional    = true;
    foreach ($this->_constraints as $constraint) {
      /** @noinspection PhpUndefinedMethodInspection */
      $this->_optional = $constraint->isOptional() && $this->_optional;
    }
  }

  public function execute($value, Context $context) {
    if (empty($this->_constraints)) {
      throw new \BeatsBundle\Exception\Exception("Constraint chain is empty");
    }
    if ($this->_constraints instanceof \Traversable) {
      return parent::execute($value, $context);
    }
    throw new \BeatsBundle\Exception\Exception("Invalid constraint chain");
  }

  private function _execute(Constraint $constraint, &$value, Context $context) {
    $success = $constraint->execute($value, $context);

    $this->_original    = $constraint->getOriginal();
    $this->_transformed = $constraint->getTransformed();

    $value = $this->_transformed;
    if ($success) {
      if (!is_string($this->_messageSuccess)) {
        if ($this->_strict) {
          $this->_messageSuccess[] = $constraint->getMessageSuccess();
        } else {
          $this->_messageSuccess = $constraint->getMessageSuccess();
        }
      }
    } else {
      if (!is_string($this->_messageFailure)) {
        if ($this->_strict) {
          $this->_messageFailure[] = $constraint->getMessageFailure();
        } else {
          $this->_messageFailure = $constraint->getMessageFailure();
        }
      }
    }
    return $success;
  }

  public function validate($value, Context $context) {
    if ($this->_isOR) {
      $success = false;
      if ($this->_strict) {
        foreach ($this->_constraints as $constraint) {
          $success = $this->_execute($constraint, $value, $context) || $success;
        }
      } else {
        foreach ($this->_constraints as $constraint) {
          if ($this->_execute($constraint, $value, $context)) {
            return true;
          }
        }
      }
    } else {
      $success = true;
      if ($this->_strict) {
        foreach ($this->_constraints as $constraint) {
          $success = $this->_execute($constraint, $value, $context) && $success;
        }
      } else {
        foreach ($this->_constraints as $constraint) {
          if (!$this->_execute($constraint, $value, $context)) {
            return false;
          }
        }
      }
    }
    return $success;
  }

}