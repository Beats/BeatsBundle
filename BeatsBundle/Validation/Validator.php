<?php
namespace BeatsBundle\Validation;

//use Symfony\Component\DependencyInjection\ContainerAware;

use BeatsBundle\Http\ParameterBag;
use BeatsBundle\Service\ContainerAware;

class Validator extends ContainerAware {


  /**
   * @var Context
   */
  protected $_context;


  /**
   * @var ParameterBag
   */
  protected $_messages;

  /**
   * @return Context
   */
  public function getContext() {
    if (empty($this->_context)) {
      $this->_context = new Context($this->container);
    }
    return $this->_context;
  }

  /**
   * @param Context $context
   * @return Validator
   */
  public function setContext($context) {
    $this->_context = $context;
    return $this;
  }

  public function getMessages() {
    return empty($this->_messages) ? $this->getContext()->getMessages() : $this->_messages;
  }

  public function setMessages($messages = null) {
    $this->_messages = $messages;
  }


  /**
   * @return Validator
   */
  public function reset() {
    $this->_context = null;
    return $this;
  }

  /**
   * @param Validation $validation
   * @param mixed $value
   * @param bool $throws
   * @return Validation
   */
  public function validate(Validation $validation, $value, $throws = true) {
    $context = $this->getContext()->validate($validation, $value);
    if ($throws) {
      $context->throwErrors();
    }
    return $validation;
  }

  /**
   * @param ValidationBag $validations
   * @param bool $throws
   * @param array|null $values
   * @return Context
   */
  public function process(ValidationBag $validations, $throws = true, $values = null) {
    return $this->getContext()->process($validations, $throws, $values);
  }
}




