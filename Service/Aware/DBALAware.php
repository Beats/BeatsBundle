<?php
namespace BeatsBundle\Service\Aware;

use BeatsBundle\DBAL\AbstractDBAL;

trait DBALAware {

  /**
   * @param $name
   * @throws \Exception
   * @return AbstractDBAL
   */
  final protected function _dbal($name) {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('beats.dbal.' . $name);
  }
}






