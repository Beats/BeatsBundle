<?php
namespace BeatsBundle\DBAL;

// LATER: Remove this in future revisions - The DB layer shouldn't be aware of an Entity. Create a DBAL given callback

use BeatsBundle\Entity\AbstractEntity;
use BeatsBundle\Exception\DBALException;
use BeatsBundle\Helper\UTC;

abstract class AbstractDB {

  const CREATED = 'created';

  static private $_pk_prefix = 'id_';

  static private $_table_prefix = 'rdb_';

  static private $_collection_suffix = 's';

  static public function pk($model) {
    return self::$_pk_prefix . strtolower($model);
  }

  static public function table($model) {
    return self::$_table_prefix . $model . 's';
  }

  static public function tables(array $models) {
    return array_map(function ($model) {
      return AbstractDB::table($model);
    }, $models);
  }

  static public function collection($model) {
    return $model . self::$_collection_suffix;
  }

  /**
   * Returns the document generated id
   * @param string $model
   * @param string $id
   * @return string
   */
  static public function domID($model, $id = null) {
    if (empty($id)) {
      $id = self::uuid();
    }
    return self::collection($model) . '_' . $id;
  }

  static public function rdbID($model, $_id) {
    return preg_replace('#^[a-z]+_#i', '', $_id);
  }


  /**
   * Returns UUID to use as DOM only object ID
   * @return string
   */
  public static function uuid() {
    return UUID::v4();
  }

  public static function created(array &$data) {
    if (empty($data[self::CREATED])) {
      $data[self::CREATED] = UTC::toTimestamp();
    }
    return $data;
  }

  protected function _setup(array &$data, $model, $pk, $insert = true, $sequence = false) {
    return self::created($data);
  }

  /**
   * Returns the current version of the DB layer
   * @return string
   */
  abstract public function version();


  public function inTransaction() {
    return false;
  }

  public function start() {
    return false;
  }

  public function commit() {
    return false;
  }

  public function revert() {
    return false;
  }

  public function transaction(\Closure $callback) {
    $args    = func_get_args();
    $args[0] = $this;
    return call_user_func_array($callback, $args);
  }


  /**
   * Locates an entity by it ID
   * @param string $model
   * @param mixed $id
   * @return object|mixed
   */
  abstract public function locate($model, $id);


  // Table Gateway pattern

  /**
   * @param $model
   * @param array $where
   * @param array $order
   * @param int $limit
   * @param int $offset
   * @param bool $equal
   * @return object[]|mixed
   */
  abstract public function select($model, array $where = array(), array $order = array(), $limit = 0, $offset = 0, $equal = true);

  /**
   * Insert a new record
   *
   * @param string $model
   * @param array $data
   * @param bool $sequence
   * @return mixed The primary key
   */
  abstract public function insert($model, array $data, $sequence = false);

  /**
   * @param string $model
   * @param array $data
   * @internal param mixed $id
   * @return mixed The primary key
   */
  abstract public function update($model, array $data);

  /**
   * @param string $model
   * @param mixed $id
   * @param null|mixed $rev
   * @return mixed
   */
  abstract public function devour($model, $id, $rev = null);

  /**
   * @param string $model
   * @return mixed
   */
  abstract public function truncate($model);

  // Active Record pattern

  /**
   * @param $model
   * @param array $data
   * @param bool $sequence
   * @return mixed The primary key
   */
  public function persist($model, array $data, $sequence = false) {
    $pk = self::pk($model);
    if (empty($data[$pk])) {
      return $this->insert($model, $data, $sequence);
    } else {
      return $this->update($model, $data);
    }
  }

  /**
   * @param string $model
   * @param array $data
   * @param bool $sequence
   * @return mixed
   */
  public function save($model, array $data, $sequence = false) {
    $id = $this->persist($model, $data, $sequence);
    return $this->locate($model, $id);
  }


  public function saveAll($model, array $rows, $sequence, $callback = null) {
    $rs = array();
    if (is_callable($callback)) {
      foreach ($rows as $data) {
        $rs[] = $this->save($model, call_user_func($callback, $data), $sequence);
      }
    } else {
      foreach ($rows as $data) {
        $rs[] = $this->save($model, $data, $sequence);
      }
    }
    return $rs;
  }

  /**
   * @param string $model
   * @param array $data
   * @return mixed
   */
  abstract public function kill($model, array $data);


  abstract public function copy($model, $srcID, $sequence = false);

  /**
   * @param string|int $id
   * @param string $parentField
   * @param string $model
   * @param AbstractEntity[] $children
   * @param bool $includeDetached
   * @param bool $map
   * @param bool $childSequence
   * @return AbstractDB
   */
  public function attachAll($id, $parentField, $model, &$children,
                            $includeDetached = false, $map = true, $childSequence = false
  ) {
    if (empty($children)) {
      $children = array();
    } else {
      if ($map) {
        $map = array();
        foreach ($children as &$child) {
          if ($child instanceof AbstractEntity) {
            $childID       = $this->attachOne($id, $parentField, $child, $childSequence);
            $map[$childID] = $child;
          } else {
            $childID = $this->detachOne($model, $child);
            if ($includeDetached) {
              $map[$childID] = $child;
            }
          }
        }
        $children = $map;
      } else {
        $list = array();
        foreach ($children as &$child) {
          if ($child instanceof AbstractEntity) {
            $this->attachOne($id, $parentField, $child, $childSequence);
            $list[] = $child;
          } else {
            $this->detachOne($model, $child);
            if ($includeDetached) {
              $list[] = $child;
            }
          }
        }
        $children = $list;
      }
    }
    return $this;
  }

  /**
   * @param string|int $id
   * @param string $parentField
   * @param AbstractEntity $child
   * @param bool $childSequence
   * @param int|bool $type
   * @return mixed
   */
  public function attachOne($id, $parentField, AbstractEntity &$child, $childSequence = false, $type = false) {
    if (empty($child)) {
      return null;
    }
    $child->$parentField = $id;

    $child->tidy();

    $childID = $this->persist($child::getModel(), $child->_toDB($type), $childSequence);
//    $child = $this->locate($child::getModel(), $childID); // Missing created field

    $child->setID($childID);

    return $childID;
  }

  /**
   * @param string $model
   * @param array $children
   * @return $this
   */
  public function detachAll($model, &$children) {
    if (!empty($children)) {
      foreach ($children as &$child) {
        if ($child instanceof AbstractEntity) {
          $this->detachOne($model, $child->getID());
        } else {
          $this->detachOne($model, $child);
        }
      }
    }
    return $this;
  }

  /**
   * @param string $model
   * @param string $childID
   * @return mixed
   */
  public function detachOne($model, $childID) {
    return $this->devour($model, $childID);
  }

  public function detachChilds($model, $parentPK, $parentID) {
    throw new DBALException(__METHOD__ . ' not implemented');
  }

}