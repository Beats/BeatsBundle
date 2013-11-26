<?php
namespace BeatsBundle\Scheduler;

use BeatsBundle\Exception\SchedulerException;
use BeatsBundle\Helper\UTC;
use BeatsBundle\Scheduler\Task\AbstractTask;
use BeatsBundle\Scheduler\Task\AtTask;

class AtScheduler extends AbstractScheduler {

  private function _parseResponse($response = null) {
    if (empty($response)) {
      return false;
    }

    preg_match('/job (?<id>\d+) at (?<time>\d{1,4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2})/', $response, $match);
    if (count($match) < 1) {
      return false;
    }

    return array(
      'id'   => intval($match['id']),
      'time' => UTC::createDateTime($match['time'])
    );

  }

  private function _gatATQResponse($data) {
    if (empty($data)) {
      return array();
    }

    $lines   = explode("\n", trim($data));
    $cmdList = array();
    foreach ($lines as $line) {
      $lineData = explode("\t", $line);
//      preg_match('/^(\d{1,4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2})/', $lineData[1], $matches);
//      $cmdList[] = array('id' => intval($lineData[0]), 'time' => UTC::createDateTime($matches[0]));
      $cmdList[] = intval($lineData[0]);
    }

    return $cmdList;
  }

  private function _setStatus(AbstractTask &$task) {
    $inQueue = $this->_gatATQResponse(`atq`);
    if (in_array($task->id_job, $inQueue)) {
      $task->status = AtTask::TASK_AWAIT;
    } else {
      $task->status = AtTask::TASK_ENDED;
    }
  }

  public function add($command = null, $time = null, $params = array(), $name = null) {
    if (empty($command) || empty($time)) {
      return null;
    }

    $task = new AtTask(array(
      'command' => $command,
      'name'    => empty($name) ? $command : $name,
      'time'    => $time,
      'params'  => $params
    ));

    $saved = false;

    try {
      $task       = $this->_saveTask($task);
      $exec       = $task->createExecString();
      $atResponse = `$exec`;
      $response   = $this->_parseResponse($atResponse);
      if ($response) {
        $task->id_job = $response['id'];
        $task         = $this->_saveTask($task);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $saved        = true;
      } else {
        throw new SchedulerException('At command has failed!');
      }
    } catch (SchedulerException $ex) {
      if (!$saved) {
        $this->_deleteTask($task);
      }

      return null;
    } catch (\Exception $ex) {
      return null;
    }

    return $task;
  }

  public function remove($taskId = 0) {

    try {
      $task = $this->fetch($taskId);

      if (!empty($task)) {
        $exec     = 'atrm ' . $task->id_job;
        $response = `$exec`;
        if (empty($response)) {
          $this->_deleteTask($task);

          return true;
        }
      }
    } catch (\Exception $ex) {
      return false;
    }

    return false;
  }

  public function fetch($taskId = 0) {
    $task = $this->_fetchTask(AtTask::getModel(), $taskId);
    $this->_setStatus($task);

    return $task;
  }

  public function inventory() {
    $tasks = $this->_listTask(AtTask::getModel());
    foreach ($tasks as &$task) {
      $this->_setStatus($task);
    }

    return $tasks;
  }
}