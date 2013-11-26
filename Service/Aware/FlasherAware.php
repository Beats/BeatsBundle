<?php
namespace BeatsBundle\Service\Aware;

use BeatsBundle\Session\Flasher;

trait FlasherAware {

  /**
   * @return Flasher
   */
  final protected function _flasher() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('beats.flasher');
  }

}






