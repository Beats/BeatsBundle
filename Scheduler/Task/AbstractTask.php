<?php
namespace BeatsBundle\Scheduler\Task;

use BeatsBundle\Entity\AbstractEntity;

abstract class AbstractTask extends AbstractEntity {

  const TASK_AWAIT = 0x00000000;
  const TASK_ENDED = 0x00000001;


  /**
   * @var int
   */
  public $id_job;

  /**
   * @var string
   */
  public $name;

  /**
   * @var \DateTime
   */
  public $time;

  /**
   * @var string
   */
  public $command;

  /**
   * @var Object
   */
  public $params;

  /**
   * @var int
   */
  public $status;

  /**
   * @return string
   */
  abstract public function createExecString();
}