<?php
namespace BeatsBundle\Validation;

use BeatsBundle\Http\ParameterBag;
use BeatsBundle\Service\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Context extends ContainerAware {

  /**
   * @var Constraint
   */
  public $current;

  /**
   * @var ParameterBag
   */
  protected $_parameters;

  /**
   * @var ParameterBag
   */
  protected $_fields;

  /**
   * @var ParameterBag
   */
  protected $_validations;

  /**
   * @var ParameterBag
   */
  protected $_errors;
  /**
   * @var ParameterBag
   */
  protected $_valids;
  /**
   * @var ParameterBag
   */
  protected $_messages;

  public function __construct(ContainerInterface $container, $parameters = array()) {
    parent::__construct($container);

    $this->_fields = new ParameterBag();

    $this->_parameters = new ParameterBag($parameters);

    $this->_validations = new ParameterBag();
    $this->_valids      = new ParameterBag();
    $this->_errors      = new ParameterBag();
    $this->_messages    = new ParameterBag();

  }

  /**
   * @return ContainerInterface
   */
  public function getContainer() {
    return $this->container;
  }

  /**
   * @return ParameterBag
   */
  public function getFields() {
    return $this->_fields;
  }

  /**
   * @return ParameterBag
   */
  public function getParameters() {
    return $this->_parameters;
  }

  /**
   * @return ParameterBag
   */
  public function getValidations() {
    return $this->_validations;
  }

  /**
   * @return ParameterBag
   */
  public function getErrors() {
    return $this->_errors;
  }

  /**
   * @return ParameterBag
   */
  public function getValids() {
    return $this->_valids;
  }

  /**
   * @return ParameterBag
   */
  public function getMessages() {
    return $this->_messages;
  }


  /**
   * @return boolean
   */
  public function hasErrors() {
    return $this->_errors->count();
  }

  /**
   * @return Context
   * @throws ValidationException
   */
  public function throwErrors() {
    if ($this->hasErrors()) {
      throw new ValidationException($this);
    }
    return $this;
  }

  /**
   * @param Validation $validation
   * @param mixed $value
   * @return Context
   */
  public function validate(Validation $validation, $value) {
    $success = $validation->validate($value, $this);
    $name    = $validation->getName();
    $message = $validation->getMessage();
    $this->_validations->set($name, $validation);
    $this->_messages->set($name, $message);
    if ($success) {
      $this->_valids->set($name, $message);
    } else {
      $this->_errors->set($name, $message);
    }
    return $this;
  }

  /**
   * @param ValidationBag $validations
   * @param bool $throws
   * @param array $values
   * @return Context
   */
  public function process(ValidationBag $validations, $throws = true, $values = null) {
    foreach ($validations as $field => $validation) {
      $value = $this->_getValue($field, $values);
      $this->validate($validation, $value);
      /** @noinspection PhpUndefinedMethodInspection */
      $this->_fields->set($field, $validation->getValue());
    }
    return $throws ? $this->throwErrors() : $this;
  }

  private function _getValue($field, $values, $default = null) {
    if (empty($values)) {
      $request = $this->_request();
      return $request->get($field, $request->files->get($field, $default, true), true);
    } else {
      return isset($values[$field]) ? $values[$field] : $default;
    }
  }

}
