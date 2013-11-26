<?php
namespace BeatsBundle\Model;

use BeatsBundle\Exception\Exception;
use BeatsBundle\Session\Message;

class ModelException extends Exception {

  /**
   * @var Message
   */
  public $flash;
  /**
   * @var string
   */
  public $routeName = true;
  /**
   * @var array
   */
  public $routeArgs = array();

  public function __construct($flash, $routeName = true, array $routeArgs = array(), $code = 0, Exception $previous = null) {
    if (!$flash instanceof Message) {
      $flash = Message::failure($flash);
    }
    if ($routeName instanceof \Exception) {
      $previous  = $routeName;
      $routeName = true;
    }
    $this->flash     = $flash;
    $this->routeName = $routeName;
    $this->routeArgs = $routeArgs;
    parent::__construct($flash->message, $code, $previous);
  }

  public function hasFlash() {
    return !empty($this->flash);
  }

}
