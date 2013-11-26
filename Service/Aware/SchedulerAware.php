<?php
namespace BeatsBundle\Service\Aware;


use BeatsBundle\Scheduler\AbstractScheduler;

trait SchedulerAware {

  /**
   * @return AbstractScheduler
   */
  final protected function _atScheduler() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('beats.scheduler.at');
  }

}