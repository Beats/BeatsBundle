<?php
namespace BeatsBundle\Controller\HTML;

use BeatsBundle\Controller\AbstractController;
use BeatsBundle\Session\Message;
use BeatsBundle\Session\Page;

class BasicController extends AbstractController {

  public function flashAction() {
    $data = $this->_request()->get('data');
    if (!empty($data)) {
      if ($data['redirect']) {
        $this->_flasher()->add(Message::factory($data));
        return $this->redirect($data['redirect']);
      } else {
        $this->_flasher()->setPage(Page::factory($data));
      }
    }
    return $this->render($this->_flasher()->getTemplate($this->_template()), array(
      'page'     => $this->_flasher()->getPage(),
      'messages' => $this->_flasher()->get(),
    ));
  }

  public function browserAction() {
    return $this->renderAction(array(
      'browsers' => array(
        array(
          'name' => 'Mozilla Firefox',
          'href' => 'http://www.mozilla.org/en-US/firefox/all',
          'icon' => 'firefox.png'
        ),
        array(
          'name' => 'Google Chrome',
          'href' => 'http://www.google.com/intl/us/chrome/browser',
          'icon' => 'chrome.png'
        ),
        array(
          'name' => 'Opera',
          'href' => 'http://www.opera.com/download',
          'icon' => 'opera.png'
        ),
        array(
          'name' => 'Safari',
          'href' => 'http://support.apple.com/downloads/#safari',
          'icon' => 'safari.png'
        ),
        array(
          'name' => 'Internet Explorer',
          'href' => 'http://windows.microsoft.com/en-us/internet-explorer/download-ie',
          'icon' => 'ie.png'
        ),
      ),
    ));
  }

}
