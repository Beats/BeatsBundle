<?php
namespace BeatsBundle\Validation;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Message {

  const TYPE_OPTIONAL = 'optional';
  const TYPE_INFO     = 'info';
  const TYPE_FAILURE  = 'error';
  const TYPE_WARNING  = 'warning';
  const TYPE_SUCCESS  = 'success';

  protected $_type = self::TYPE_SUCCESS;

  protected $_text;

  protected $_name;

  protected $_value;

  public function __construct($name, $text = '', $type = self::TYPE_SUCCESS, $value = null) {
    $this->_name  = $name;
    $this->_text  = is_array($text) ? $text : array($text);
    $this->_type  = $type;
    $this->_value = $value instanceof UploadedFile ? $value->getRealPath() : $value;
  }

  public function getName() {
    return $this->_name;
  }

  public function getText() {
    return $this->_text;
  }

  public function setText($text) {
    $this->_text  = is_array($text) ? $text : array($text);
    return $this;
  }

  public function getType() {
    return $this->_type;
  }

  public function isError() {
    return $this->_type != self::TYPE_SUCCESS and $this->_type != self::TYPE_OPTIONAL;
  }

  public function getValue() {
    return $this->_value;
  }

  public function __toString() {
    $text = $this->getText();
    if (is_string($text)) {
      return $text;
    }
    $texts = array();
    array_walk_recursive($text, function ($text) use (&$texts) {
      array_push($texts, $text);
    });
    return implode(";", $texts);

  }


}
