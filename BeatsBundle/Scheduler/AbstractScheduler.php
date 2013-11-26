<?php
namespace BeatsBundle\Scheduler;

use BeatsBundle\DBAL\AbstractDBAL;
use BeatsBundle\DBAL\DOM;
use BeatsBundle\Helper\UTC;
use BeatsBundle\Scheduler\Task\AbstractTask;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractScheduler extends ContainerAware {

  /**
   * @return DOM
   */
  protected function _dom() {
    return $this->container->get('beats.dbal.dom');
  }

  public function __construct(ContainerInterface $container) {
    $this->setContainer($container);
  }

  /**
   * @param string $command
   * @param string $time timestamp
   * @param array $params
   * @param string $name
   * @return bool
   */
  abstract public function add($command = null, $time = null, $params = array(), $name = null);

  abstract public function remove($taskId = 0);

  abstract public function fetch($taskId = 0);

  abstract public function inventory();


  /**
   * Operations with tasks in database
   */

  /**
   * @param AbstractTask $task
   * @return AbstractTask
   */
  protected function _saveTask($task) {
    $id = $this->_dom()->persist($task->getModel(), $task->_toDB(AbstractDBAL::P_DOM));
    return $this->_fetchTask($task->getModel(), $id);
  }

  /**
   * @param string $model
   * @param $row[]
   * @return AbstractTask
   */
  protected static function _buildTask($model, $row) {
    $class = 'BeatsBundle\\Scheduler\Task\\'.$model;
    $task = new $class((array)$row);
    $task->time = UTC::createDateTime($task->time->date);
    return $task;
  }

  /**
   * @param string $model
   * @param $id
   * @return AbstractTask
   */
  protected function _fetchTask($model, $id) {
    $row = $this->_dom()->locate($model, $id);
    return self::_buildTask($model, $row);
  }

  /**
   * @param AbstractTask $task
   * @return mixed
   */
  protected function _deleteTask($task) {
    return $this->_dom()->devour($task->getModel(), $task->getID());
  }

  protected function _listTask($model) {

    $tasks = array();
    $rows = $this->_dom()->select($model);
    foreach($rows as $row) {
      $tasks[] = self::_buildTask($model, $row);
    }

    return $tasks;
  }

}