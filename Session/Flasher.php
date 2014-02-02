<?php
namespace BeatsBundle\Session;

use BeatsBundle\Service\ContainerAware;

class Flasher extends ContainerAware {

  const FLASH_MESSAGE = 'flasher.message';
  const FLASH_PAGE    = 'flasher.page';

  /********************************************************************************************************************/

  protected function _flashBag() {
    return $this->_session()->getFlashBag();
  }

  public function set(Message $message) {
    if ($message instanceof Page) {
      return $this->setPage($message);
    }
    $this->_flashBag()->set(self::FLASH_MESSAGE, array($message));
    return $this;
  }

  public function add(Message $message) {
    if ($message instanceof Page) {
      return $this->setPage($message);
    }
    $this->_flashBag()->add(self::FLASH_MESSAGE, $message);
    return $this;
  }

  public function get() {
    return $this->_flashBag()->get(self::FLASH_MESSAGE);
  }

  public function peek() {
    return $this->_flashBag()->peek(self::FLASH_MESSAGE);
  }


  public function hasMessages() {
    return $this->_flashBag()->has(self::FLASH_MESSAGE);
  }

  /********************************************************************************************************************/

  public function hasPage() {
    return $this->_flashBag()->has(self::FLASH_PAGE);
  }

  public function setPage(Page $page) {
    if (empty($page->href)) {
      $page->href = $this->getCurrentURL();
    }
    $this->_flashBag()->set(self::FLASH_PAGE, $page);
    return $this;
  }

  public function getPage(Page $default = null) {
    if ($this->_flashBag()->has(self::FLASH_PAGE)) {
      return $this->_flashBag()->get(self::FLASH_PAGE);
    } else {
      if (empty($default)) {
        $default = Page::failure("Flash information missing. Contact support!", 'System error');
      }
      return $default;
    }
  }

  public function getTemplate($default = null) {
    $template = $this->_options->has('template') ? $this->_options->get('template'): null;
    return empty($template) ? $default : $template;
  }

  public function getCurrentURL(array $parameters = array()) {
    $attributes = $this->_request()->attributes;
    return $this->_router()->generate($attributes->get('_route'), array_merge($attributes->get('_route_params'), $parameters));
  }

  /********************************************************************************************************************/

}




