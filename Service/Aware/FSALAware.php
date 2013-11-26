<?php
namespace BeatsBundle\Service\Aware;

use BeatsBundle\FSAL\AbstractFSAL;
use BeatsBundle\FSAL\Imager;

trait FSALAware {

  /**
   * @return AbstractFSAL
   */
  final protected function _fsal() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('beats.fsal.domfs');
  }

  /**
   * @return Imager
   */
  final protected function _imager() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('beats.fsal.imager');
  }

}






