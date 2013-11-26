<?php
namespace BeatsBundle\Validation;

use BeatsBundle\Service\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class Constraint extends ContainerAware {

  protected $_messageSuccess;
  protected $_messageFailure;

  /**
   * @var bool
   */
  protected $_optional = true;
  /**
   * @var bool
   */
  protected $_empty = true;

  /**
   * LATER: Propagate the setup of this property
   * @var bool
   */
  protected $_toNULL = false;

  /**
   * @var mixed
   */
  protected $_original = null;
  /**
   * @var mixed
   */
  protected $_transformed = null;

  /********************************************************************************************************************/

  public function __construct($failure = 'The value is invalid', $success = 'The value is valid') {
    $this->_messageFailure = $failure;
    $this->_messageSuccess = $success;
  }

  public function setContainer(ContainerInterface $container = null) {
    parent::setContainer($container);
    $this->_dpiSetup();
  }

  protected function _dpiSetup() {
  }

  /********************************************************************************************************************/

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


  public function getOriginal() {
    return $this->_original;
  }

  public function getTransformed() {
    return $this->_transformed;
  }

  public function getMessageFailure() {
    return $this->_messageFailure;
  }

  public function getMessageSuccess() {
    return $this->_messageSuccess;
  }


  protected function _checkEmpty($value) {
    return null === $value || '' === $value;
  }

  /**
   * Validate method wrapper. Should not be used directly
   * @param mixed $value
   * @param Context $context
   * @return bool
   */
  public function execute($value, Context $context) {
    // LATER: Find a better way of resolving this
    $this->setContainer($context->getContainer());
    $this->_cleanup($value, $context);
    if ($this->isEmpty() && $this->isOptional()) {
      return true;
    }
    return $this->validate($value, $context);
  }

  private function _cleanup(&$value, Context $context) {
    $context->current   = $this;
    $this->_original    = $value;
    $value              = $this->transform($value);
    $this->_transformed = $value;
    $this->_empty       = $this->_checkEmpty($value);
    return $this;
  }

  public function transform($value) {
    $value = is_string($value) ? trim($value) : $value;
    if ($this->_toNULL && empty($value)) {
      return null;
    }
    return $value;
  }

  /**
   * @param mixed $value
   * @param Context $context
   * @return bool
   */
  public function validate($value, Context $context) {
    return true;
  }

}
