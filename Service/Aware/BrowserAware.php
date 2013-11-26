<?php
namespace BeatsBundle\Service\Aware;

use Buzz\Browser;

trait BrowserAware {

  /**
   * @return Browser
   */
  final protected function _browser() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('browser');
  }

}






