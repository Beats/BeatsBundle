<?php
namespace BeatsBundle\Scheduler\Task;

use BeatsBundle\Helper\UTC;

class AtTask extends AbstractTask {

  static protected $_model = 'AtTask';

  /**
   * @var int
   */
  public $id_attask = null;

  public function createExecString() {
    $exec = 'echo "' . $this->command .' '. $this->getID() . '" | at ' . UTC::toFormat('H:i m/d/Y', $this->time);

    return $exec .' 2>&1';
  }
}