<?php
namespace BeatsBundle\Session;

class Message implements \Serializable {

  const DEFAULT_MESSAGE = 'Just saying Hi :)';

  const TYPE_COUNSEL = 'info';
  const TYPE_WARNING = 'block';
  const TYPE_SUCCESS = 'success';
  const TYPE_FAILURE = 'danger';

  /********************************************************************************************************************/

  /**
   * @var string
   */
  public $type = self::TYPE_WARNING;

  /**
   * @var string
   */
  public $heading;

  /**
   * @var string
   */
  public $message;

  /********************************************************************************************************************/

  public function __construct($message = null, $type = self::TYPE_WARNING, $heading = null) {
    if (empty($message)) {
      $this->message = self::DEFAULT_MESSAGE;
      $this->type    = self::TYPE_COUNSEL;
    } else {
      $this->message = $message;
      $this->type    = $type;
    }
    $this->heading = $heading;

  }

  /********************************************************************************************************************/

  public function serialize() {
    return serialize(array(
      'type'    => $this->type,
      'message' => $this->message,
      'heading' => $this->heading,
    ));
  }

  public function unserialize($serialized) {
    $data = (object)unserialize($serialized);

    $this->type    = $data->type;
    $this->message = $data->message;
    $this->heading = $data->heading;

    return $this;
  }

  /********************************************************************************************************************/

  static public function factory(array $data) {
    if (empty($data)) {
      return new self();
    }
    $data = (object)array_merge(array(
      'message' => null,
      'type'    => null,
      'heading' => null,
    ), $data);
    return new self($data->message, $data->type, $data->heading);
  }

  static public function exception(\Exception $ex) {
    return static::failure($ex->getMessage());
  }

  /********************************************************************************************************************/

  static public function success($message, $heading = null) {
    return new static($message, self::TYPE_SUCCESS, $heading);
  }

  static public function failure($message, $heading = null) {
    return new static($message, self::TYPE_FAILURE, $heading);
  }

  static public function warning($message, $heading = null) {
    return new static($message, self::TYPE_WARNING, $heading);
  }

  static public function counsel($message, $heading = null) {
    return new static($message, self::TYPE_COUNSEL, $heading);
  }

  /********************************************************************************************************************/

}
