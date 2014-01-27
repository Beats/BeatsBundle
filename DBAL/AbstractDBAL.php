<?php
namespace BeatsBundle\DBAL;

use BeatsBundle\Entity\AbstractEntity;
use BeatsBundle\Exception\DBALException;
use BeatsBundle\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Performs RDB|DOM actions.
 *
 * NO BUSINESS LOGIC GOES HERE
 *
 * only sql and map-reduce is stored here
 *
 */
class AbstractDBAL extends ContainerAware {

  const P_RDB = 1;
  const P_DOM = 2;
  const P_MIX = 3;

  /**
   * DBAL type
   * @var int
   */
  private $_type;

  /**
   * The entity db model name
   * @var string
   */
  protected $_model;

  /**
   * The entity class name
   * @var string
   */
  protected $_entity;

  /**
   * Does the DB layer generates the id as a RDB sequence or the id is an UUID
   * @var bool
   */
  private $_sequence = false;

  /********************************************************************************************************************/

  private function _setup($class) {
    preg_match('#^(?<prefix>(\w+\\\\)*\w+)Bundle\\\\DBAL\\\\(?<model>\w+)s$#', $class, $matches);
    if (empty($matches['model'])) {
      throw new Exception("Model name not found");
    }
    $this->_model  = strtolower($matches['model']);
    $this->_entity = sprintf("%sBundle\\Entity\\%s", $matches['prefix'], $matches['model']);
    return $this;
  }

  public function __construct(ContainerInterface $container, $type = self::P_MIX, $sequence = false) {
    $this->setContainer($container);
    if (($type | self::P_MIX) != self::P_MIX) {
      throw new Exception("Invalid DBAL type {$type}");
    }
    $this->_type = $type;

    $this->_sequence = is_bool($sequence) ? $sequence : (bool)($sequence | 0);

    // TODO@ion: Refactor all this to be performed during the cache:warmup stage
    $this->_setup(get_class($this));
  }

  /********************************************************************************************************************/

  /**
   * @return RDB
   * @throws Exception
   */
  public function rdb() {
    if ($this->isRDB()) {
      return $this->container->get('beats.dbal.rdb');
    }
    throw new Exception("RDB not supported by this DBAL");
  }

  /**
   * @return DOM
   * @throws Exception
   */
  public function dom() {
    if ($this->isDOM()) {
      return $this->container->get('beats.dbal.dom');
    }
    throw new Exception("DOM not supported by this DBAL");
  }

  /**
   * @return MIX
   * @throws Exception
   */
  public function mix() {
    if ($this->isMIX()) {
      return $this->container->get('beats.dbal.mix');
    }
    throw new Exception("MIX not supported by this DBAL");
  }

  /********************************************************************************************************************/

  /**
   * Returns the DBAL type: RDB, DOM, MIX
   * @return int
   */
  public function getType() {
    return $this->_type;
  }

  public function isSequence() {
    return $this->_sequence;
  }

  /********************************************************************************************************************/

  /**
   * Returns true if this DBAL has RDB persistence
   * @param bool $strict
   * @param int|null $type
   * @return bool
   */
  public function isRDB($strict = false, $type = null) {
    return self::_isFlag($this->_type($type), self::P_RDB, $strict);
  }

  /**
   * Returns true if this DBAL has DOM persistence
   * @param bool $strict
   * @param int|null $type
   * @return bool
   */
  public function isDOM($strict = false, $type = null) {
    return self::_isFlag($this->_type($type), self::P_DOM, $strict);
  }

  /**
   * Returns true if this DBAL has MIX persistence
   * @param bool $strict
   * @param int|null $type
   * @return bool
   */
  public function isMIX($strict = false, $type = null) {
    return self::_isFlag($this->_type($type), self::P_MIX, true);
  }

  static protected function _isFlag($union, $flag, $strict = false) {
    return (bool)($strict ? $union == $flag : $union & $flag);
  }

  /********************************************************************************************************************/

  public function version() {
    return (object)array(
      'rdb' => $this->isRDB() ? $this->rdb()->version() : 'N/a',
      'dom' => $this->isDOM() ? $this->dom()->version() : 'N/a',
    );
  }

  /********************************************************************************************************************/

  /**
   * Returns a registered
   * @param string $model
   * @return AbstractDBAL
   */
  final protected function _dbal($model) {
    return $this->container->get('beats.dbal.' . $model);
  }

  /**
   * Returns a DBAL db type
   * Either forced or default.
   * @param $type
   * @return int
   */
  final protected function _type($type) {
    return empty($type) ? $this->_type : $type;
  }

  /**
   * @param int $type
   * @return AbstractDB
   * @throws Exception
   */
  final protected function _db($type = null) {
    switch ($this->_type($type)) {
      case self::P_DOM:
        if ($this->isMIX()) {
          return $this->mix()->dom();
        }
        return $this->dom();
      case self::P_RDB:
        if ($this->isMIX()) {
          return $this->mix()->rdb();
        }
        return $this->rdb();
      case self::P_MIX:
        return $this->mix();
      default:
        throw new Exception("Invalid db type [$type]");
    }
  }

  protected function _toEntity($id = null, $class = null) {
    if (empty($class)) {
      $class = $this->_model;
    }
    $entity = new $class();
    if ($entity instanceof AbstractEntity) {
      return $entity->setID($id);
    }
    throw new Exception("Invalid Entity class [$class]");
  }

  /**
   * Builds an entity out of raw data received from the DB
   * @param array $row
   * @param null $class
   * @internal param bool $map Whether to return a vector or an associative array of ID -> Entity
   * @return AbstractEntity
   */
  protected function _buildEntity($row, $class = null) {
    if (empty($row)) {
      return null;
    }
    if (empty($class)) {
      $class = $this->_entity;
    }
    return new $class((array)$row);
  }

  /**
   * Builds an array of entities out of raw data received from the DB
   * @param array $rows
   * @param bool $map Whether to return a vector or an associative array of ID -> Entity
   * @param null $class
   * @return array
   */
  protected function _buildCollection($rows, $map = false, $class = null) {
    if (empty($rows)) {
      return array();
    }
    $entities = array();
    if ($map) {
      foreach ($rows as $row) {
        $entity                     = $this->_buildEntity($row, $class);
        $entities[$entity->getID()] = $entity;
      }
    } else {
      foreach ($rows as $row) {
        $entities[] = $this->_buildEntity($row, $class);
      }
    }
    return $entities;
  }

  /********************************************************************************************************************/

  public function inTransaction() {
    if ($this->isRDB()) {
      return $this->_db()->inTransaction();
    }
    throw new Exception("DOM does not support transactions!");
  }

  /**
   * @throws Exception
   */
  public function start() {
    if ($this->isRDB()) {
      return $this->_db()->start();
    }
    throw new Exception("DOM does not support transactions!");
  }

  public function commit() {
    if ($this->isRDB()) {
      return $this->_db()->commit();
    }
    throw new Exception("DOM does not support transactions!");
  }

  public function revert() {
    if ($this->isRDB()) {
      return $this->_db()->revert();
    }
    throw new Exception("DOM does not support transactions!");
  }

  public function transaction(\Closure $callback) {
    if ($this->isRDB()) {
      try {
        $this->start();
        $args    = func_get_args();
        $args[0] = $this;
        $result  = call_user_func_array($callback, $args);
        if ($result === false) {
          throw new Exception("Could not insert data in RDB");
        }
        $this->commit();
        return $result;
      } catch (Exception $ex) {
        $this->revert();
        throw $ex;
      } catch (\Exception $ex) {
        $this->revert();
        throw new Exception("Transaction error", 0, $ex);
      }
    }
    throw new Exception("DOM does not support transactions!");
  }

  /********************************************************************************************************************/

  /**
   * @param $id
   * @param null $class
   * @return AbstractEntity|object
   */
  private function _locateDeep($id, $class = null) {
    if (empty($class)) {
      $class = $this->_entity;
    }
    /** @noinspection PhpUndefinedMethodInspection */
    /** @noinspection PhpUndefinedMethodInspection */
    /** @noinspection PhpUndefinedMethodInspection */
    /** @noinspection PhpUndefinedMethodInspection */
    $row = $this->rdb()->locateDeep(
      $class::getModel(), $class::getParent(), $class::getChilds(), $class::getJoints(), $id
    );
    return $this->_buildEntity($row, $class);
  }

  /**
   * @param mixed $id
   * @param bool $deep
   * @param int|null $type
   * @return AbstractEntity|null
   */
  public function locate($id, $deep = true, $type = null) {
    if ($deep && $this->isRDB(true, $type)) {
      return $this->_locateDeep($id);
    } else {
      return $this->_buildEntity($this->_db($type)->locate($this->_model, $id));
    }
  }

  /**
   * @param $id
   * @param bool $deep
   * @param null $type
   * @return AbstractEntity
   * @throws \BeatsBundle\Exception\Exception
   */
  public function fetch($id, $deep = true, $type = null) {
    $entity = $this->locate($id, $deep, $type);
    if (empty($entity)) {
      throw new Exception(ucfirst($this->_model) . ' not found: ' . $id);
    }
    return $entity;
  }

  /**
   * @param array $where
   * @param array $order
   * @param int $limit
   * @param int $offset
   * @param bool $equal
   * @param null $type
   * @return AbstractEntity[]
   */
  public function select(array $where = array(), array $order = array(), $limit = 0, $offset = 0, $equal = true, $type = null) {
    return $this->_buildCollection($this->_db($type)->select($this->_model, $where, $order, $limit, $offset, $equal));
  }

  /********************************************************************************************************************/

  public function save(AbstractEntity $entity, $type = null) {
    if ($this->isMIX() && $this->isMIX(false, $type)) {
      $id = $this->_db()->transaction(function (RDB $rdb, DOM $dom, $sequence, AbstractEntity $entity) {

        $model = $entity::getModel();

        if ($entity->hasID()) {
          $id = $rdb->update($model, $entity->_toDB(AbstractDBAL::P_RDB));
        } else {
          $id = $rdb->insert($model, $entity->_toDB(AbstractDBAL::P_RDB), $sequence);
        }
        if ($id === false) {
          throw new Exception("Couldn't insert data into MIX->RDB [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
        }

        return $dom->transaction(function (DOM $dom, $model, AbstractEntity $entity, $id) {
          if ($entity->hasID()) {
            $entity->setID($id);
            return $dom->update($model, $entity->_toDB(AbstractDBAL::P_DOM));
          } else {
            $entity->setID($id);
            return $dom->insert($model, $entity->_toDB(AbstractDBAL::P_DOM));
          }
        }, $model, $entity, $id);
      }, $this->isSequence(), $entity);

      $result = $this->locate($id);
    } else {
      $result = $this->_buildEntity($this->_db($type)->save(
        $entity::getModel(), $entity->_toDB($this->_type($type)), $this->isSequence()
      ));
    }
    if (empty($result)) {
      throw new Exception("Entity not saved: " . $entity::getModel());
    }
    return $result;
  }

  public function kill(AbstractEntity $entity, $type = null) {
    return $this->_db($type)->devour($entity::getModel(), $entity->getID());
  }

  public function copy(AbstractEntity $entity, $type = null) {
    if ($this->isMIX() && $this->isMIX(false, $type)) {
      return $this->_mixCopy($entity, $type);
    } else {
      $id = $this->_db($type)->copy(
        $entity::getModel(), $entity->getID(), $this->isSequence()
      );
    }
    $result = $this->locate($id);
    if (empty($result)) {
      throw new Exception("Entity not saved: " . $entity::getModel());
    }
    return $result;
  }

  /**
   * @deprecated
   *
   * @param $id
   * @param null $type
   * @return mixed
   */
  public function drop($id, $type = null) {
    return $this->_db($type)->devour($this->_model, $id);
  }

  public function truncate($type = null) {
    return $this->_db($type)->truncate($this->_model);
  }

  /********************************************************************************************************************/

  protected function _mixCopy(AbstractEntity $entity, $type = null) {
    $dstID = null;
    $srcID = $entity->getID();
    $model = $entity::getModel();
    try {
      $dstID     = $this->dom()->copy($model, $srcID, $this->isSequence());
      $newEntity = $this->locate($dstID, false, self::P_DOM);

      // Insert any reset logic here

      $dstID = $this->rdb()->insert($model, $newEntity->_toDB(AbstractDBAL::P_RDB));
      return $this->save($newEntity, $type);
    } catch (\Exception $ex) {
      if (!empty($dstID)) {
        $this->dom()->devour($entity::getModel(), $dstID);
      }
      throw new Exception("Failed to copy document [src: $srcID, dst: $dstID");
    }
  }

  /********************************************************************************************************************/

  protected function _rdbSave(AbstractEntity $entity, $callback = null) {
    if (true)
      throw new Exception(__METHOD__ . ' not implemented');
    return null;
  }

  protected function _domSave(AbstractEntity $entity) {
    if (true)
      throw new Exception(__METHOD__ . ' not implemented');
    return null;
  }

  protected function _mixSave(AbstractEntity $entity, $type = null) {
    if ($this->isMIX() && $this->isMIX(false, $type)) {
      $row = $this->_rdbSave($entity, array($this, '_domSave'));
    } elseif ($this->isMIX() && $this->isDOM(false, $type)) {
      $row = $this->_domSave($entity);
    } elseif ($this->isMIX() && $this->isRDB(false, $type)) {
      $row = $this->_rdbSave($entity);
    } else {
      throw new Exception("Invalid DB type [$type]");
    }
    $result = $this->_buildEntity($row);
    if (empty($result)) {
      throw new Exception("Entity not saved: " . $entity::getModel());
    }
    return $result;
  }

  /********************************************************************************************************************/

  protected function _rdbKill(AbstractEntity $entity, $callback = null) {
    if (true)
      throw new Exception(__METHOD__ . ' not implemented');
    return null;
  }

  protected function _domKill(AbstractEntity $entity) {
    if (true)
      throw new Exception(__METHOD__ . ' not implemented');
    return null;
  }

  protected function _mixKill(AbstractEntity $entity, $type = null) {
    if ($this->isMIX() && $this->isMIX(false, $type)) {
      $out = $this->_rdbKill($entity, array($this, '_domKill'));
    } elseif ($this->isMIX() && $this->isDOM(false, $type)) {
      $out = $this->_domKill($entity);
    } elseif ($this->isMIX() && $this->isRDB(false, $type)) {
      $out = $this->_rdbKill($entity);
    } else {
      throw new Exception("Invalid DB type [$type]");
    }
    return $out;
  }

  /********************************************************************************************************************/

  protected function _filter($sql, $params) {
    $statement = $this->rdb()->pdo()->prepare($sql);
    foreach ($params as $key => $val) {
      $statement->bindValue($key, $val);
    }

    if (!$statement->execute()) {
      $error = $statement->errorInfo();
      throw new DBALException($error[2]);
    }

    $ids = $statement->fetchAll(\PDO::FETCH_COLUMN);

    $rows = array();
    if (empty($ids)) {
      return $rows;
    }
    $data = $this->dom()->rug()->db()->herd($ids, true, true);

    foreach ($data->rows as $row) {
      if ($row->value->deleted) {
        continue;
      }
      $rows[] = self::_buildEntity($row->doc);
    }
    return $rows;
  }

  /********************************************************************************************************************/
}
