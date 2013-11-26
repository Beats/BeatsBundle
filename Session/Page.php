<?php
namespace BeatsBundle\Session;

class Page extends Message {

  /********************************************************************************************************************/

  /**
   * HTML HEAD title
   * @var string
   */
  public $title;

  /**
   * @var string
   */
  public $href;

  /********************************************************************************************************************/

  public function __construct($message = null, $type = self::TYPE_WARNING, $heading = null, $title = null, $href = null) {
    parent::__construct($message, $type, $heading);
    $this->href  = $href;
    $this->title = self::_buildTitle($title, $type);
  }

  private static function _buildTitle($title, $type) {
    $title = trim($title);
    if (empty($title)) {
      switch ($type) {
        case self::TYPE_WARNING:
          return 'Message';
        case self::TYPE_COUNSEL :
          return 'Information';
        case self::TYPE_SUCCESS:
          return 'Success';
        case self::TYPE_FAILURE:
          return 'Error';
      }
      return 'Flash';
    }
    return $title;
  }

  /********************************************************************************************************************/

  public function serialize() {
    return serialize(array(
      'parent' => parent::serialize(),
      'href'   => $this->href,
      'title'  => $this->title,
    ));
  }

  public function unserialize($serialized) {
    $data = (object)unserialize($serialized);

    parent::unserialize($data->parent);
    $this->href  = $data->href;
    $this->title = $data->title;

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
      'title'   => null,
      'href'    => null,
    ), $data);
    return new self($data->message, $data->type, $data->heading, $data->title, $data->href);
  }

  /********************************************************************************************************************/

}
