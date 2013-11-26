<?php
namespace BeatsBundle\DBAL;

use BeatsBundle\Entity\AbstractEntity;
use BeatsBundle\Exception\DBALException;
use PDO;
use PDOStatement;

/**
 * Implement PDO or what ever to access PgSQL
 */
class RDB extends AbstractDB {

  /**
   * @var PDO
   */
  protected $_db;

  /**
   * @var mixed
   */
  protected $_execute;

  public function __construct($config) {
    // LATER: Read the configuration the correct way;
    $connection_string = sprintf("%s:host=%s;port=%d;dbname=%s;user=%s;password=%s",
      $config['drvr'],
      $config['host'],
      $config['port'],
      $config['name'],
      $config['user'],
      $config['pass']
    );

    $this->_db = new PDO($connection_string);
  }

  static public function link($modelL, $modelR) {
    return self::table($modelL . 's_' . $modelR);
  }

  static public function linkColumns($modelL, $modelR) {
    return implode(',', array(self::pk($modelL), self::pk($modelR)));
  }


  /**
   * Returns type of PDO parameter for binding
   * @param $field
   * @param $value
   * @return int
   */
  static public function pdoParam($field, $value) {
    if (is_int($value)) {
      return PDO::PARAM_INT;
    } else if (is_bool($value)) {
      return PDO::PARAM_BOOL;
    } else if (is_null($value)) {
      return PDO::PARAM_NULL;
    } else {
      return PDO::PARAM_STR;
    }
  }

  static public function pdoBind(PDOStatement $statement, $model, $data, $avoidPK = false) {
    if ($avoidPK) {
      foreach ($data as $field => $value) {
        if ($field == self::pk($model)) {
          continue;
        }
        $statement->bindValue(":$field", $value, self::pdoParam($field, $value));
      }
    } else {
      foreach ($data as $field => $value) {
        $statement->bindValue(":$field", $value, self::pdoParam($field, $value));
      }
    }
  }

  static public function excluded($key) {
    return ($key[0] == '_' || $key[0] == '$');
  }

  /**
   * @param $data
   * @param $model
   * @param bool $avoidPK
   * @throws DBALException
   * @return array
   */
  protected function _clean($data, $model, $avoidPK = false) {
    $fields = array();
    if ($avoidPK) {
      foreach ($data as $field => $value) {
        if (self::excluded($field) || $field == self::pk($model) || is_null($value)) {
          continue;
        }
        if (is_array($value)) {
          throw new DBALException("Invalid value in [$model]:$field  array");
          continue;
        } elseif (is_object($value)) {
          throw new DBALException("Invalid value in [$model]:$field  object");
          continue;
        }
        $fields[$field] = $value;
      }
    } else {
      foreach ($data as $field => $value) {
        if (self::excluded($field) || is_null($value)) {
          continue;
        }
        if (is_array($value)) {
          throw new DBALException("Invalid value in [$model]:$field  array");
          continue;
        } elseif (is_object($value)) {
          throw new DBALException("Invalid value in [$model]:$field  object");
          continue;
        }
        $fields[$field] = $value;
      }
    }
    return $fields;
  }


  public function execute(PDOStatement $statement, array $parameters = null) {
    $this->_execute = $statement->execute($parameters);
    if ($this->_execute === false) {
      throw new DBALException(implode(" ", $statement->errorInfo()));
    }
    return $this->_execute;
  }

  public function sqlSets(array $data, $model = null, $avoidPK = false, $equal = true) {
    if ($avoidPK) {
      $t = array();
      foreach ($data as $k => $v) {
        if ($k != self::pk($model)) {
          $t[$k] = $v;
        }
      }
      $data = $t;
    }
    $fields = array_keys($data);
    if ($equal) {
      return array_map(function ($field) {
        return "$field = :$field";
      }, $fields);
    } else {
      return array_map(function ($field) {
        return "$field <> :$field";
      }, $fields);
    }
  }

  public function limit($limit, $offset) {
    $limit_offset = '';
    //OFFSET makes no sense without LIMIT
    if ($limit != 0) {
      $limit_offset = " limit $limit";
      if ($offset != 0) {
        $limit_offset .= " offset $offset";
      }
    }
    return $limit_offset;
  }


  public function order($order) {
    if (empty($order)) {
      return '';
    }
    $fields = array();
    foreach ($order as $field => $direction) {
      $fields[] = $field . ' ' . $direction;
    }
    return implode(',', $fields);
  }

  public function sqlLIST(array $data) {
    $list = array();
    foreach ($data as $field => $value) {
      $list[] = "$field $value";
    }
    return $list;
  }

  /**
   * @param $model
   * @param array $where
   * @param array $order
   * @param int $limit
   * @param int $offset
   * @param bool $equal
   * @return int
   */
  public function buildSelect($model, array $where, array $order, $limit, $offset, $equal = true) {

    $sets = $this->sqlSets($where, $equal);
    $list = $this->sqlLIST($order);

    $query = sprintf("SELECT * FROM %s %s", self::table($model), $this->limit($limit, $offset));

    if (count($where)) {
      $query = sprintf("%s WHERE (%s)", $query, implode(') AND (', $sets));
    }

    if (count($order)) {
      $query = sprintf("%s ORDER BY %s ", $query, implode(', ', $list));
    }

    return $query;
  }

  /**
   * @return PDO
   */
  public function pdo() {
    return $this->_db;
  }

  public function lastInsertId($model) {
    return intval($this->_db->lastInsertId($this->table($model) . "_" . $this->pk($model) . "_seq"));
  }

  /**
   * Returns the last PDO::execute result.
   * Can be used for getting the row number returned and such
   * @return mixed
   */
  public function lastExecute() {
    return $this->_execute;
  }

  /******************************************************/

  protected function _setup(array &$data, $model, $pk, $insert = true, $sequence = false) {
    if ($insert) {
      if ($sequence) {
        unset($data[$pk]);
      } elseif (empty($data[$pk])) {
        $data[$pk] = self::uuid();
      }
    }
    return parent::_setup($data, $model, $pk, $insert, $sequence);
  }

  /**
   * Returns the current version of the DB layer
   * @return string
   */
  public function version() {
    $result = $this->_db->query("SELECT VERSION()")->fetch(PDO::FETCH_OBJ);
    return $result->version;
  }


  public function inTransaction() {
    return $this->_db->inTransaction();
  }

  public function start() {
    return $this->_db->beginTransaction();
  }

  public function commit() {
    return $this->_db->commit();
  }

  public function revert() {
    return $this->_db->rollBack();
  }

  public function transaction(\Closure $callback) {
    try {
      $this->start();
      $args    = func_get_args();
      $args[0] = $this;
      $result  = call_user_func_array($callback, $args);
      $this->commit();
      return $result;
    } catch (\Exception $ex) {
      $this->revert();
      if (!$ex instanceof DBALException) {
        $ex = new DBALException('Transaction failed', AbstractDBAL::P_RDB, $ex);
      }
      throw $ex;
    }
  }

  /********************************************************************************************************************/

  /**
   * @param string $model
   * @param mixed $id
   * @return object|mixed
   */
  public function locate($model, $id) {
    $query     = sprintf("SELECT * FROM %s WHERE %s = ? LIMIT 1", self::table($model), self::pk($model));
    $statement = $this->_db->prepare($query);
    $statement->bindParam(1, $id, PDO::PARAM_INT);
    $this->execute($statement);
    return $statement->fetch(PDO::FETCH_OBJ);
  }

  /**
   * @param string $model
   * @param array $parent
   * @param array $childs
   * @param array $joints
   * @param string $id
   * @return mixed|object
   */
  public function locateDeep($model, array $parent, array $childs, array $joints, $id) {
    $pk  = self::pk($model);
    $row = $this->locate($model, $id);
    if (empty($row)) {
      return $row;
    }
    $id = $row->$pk;

    foreach ($parent as $name => $class) {
      /** @noinspection PhpUndefinedMethodInspection */
      $row->$name = $this->parent($class::getModel(), $pk, $id);
    }

    foreach ($childs as $name => $class) {
      /** @noinspection PhpUndefinedMethodInspection */
      $row->$name = $this->childs($class::getModel(), $pk, $id);
    }

    foreach ($joints as $name => $class) {
      /** @noinspection PhpUndefinedMethodInspection */
      $row->$name = $this->joints($model, $class::getModel(), $id);
    }

    return $row;
  }


  public function select($model, array $where = array(), array $order = array(), $limit = 0, $offset = 0, $equal = true) {
    $query     = $this->buildSelect($model, $where, $order, $limit, $offset, $equal);
    $statement = $this->_db->prepare($query);
    if (count($where)) {
      self::pdoBind($statement, $model, $where);
    }
    $this->execute($statement);
    return $statement->fetchAll(PDO::FETCH_OBJ);
  }

  public function insert($model, array $data, $sequence = false) {
    $pk = self::pk($model);

    $data = $this->_clean($data, $model, $sequence);
    $data = $this->_setup($data, $model, $pk, true, $sequence);

    $fields = array_keys($data);
    $query  = sprintf("INSERT INTO %s(%s) VALUES (:%s)",
      self::table($model), implode(', ', $fields), implode(', :', $fields)
    );

    $statement = $this->_db->prepare($query);
    self::pdoBind($statement, $model, $data, $sequence);
    $this->execute($statement);
    return $sequence ? $this->lastInsertId($model) : $data[$pk];
  }

  /**
   * Update a modified record
   *
   * @param mixed $model
   * @param array $data
   * @return mixed
   */
  public function  update($model, array $data) {
    $pk   = self::pk($model);
    $data = $this->_clean($data, $model, false);
    $data = $this->_setup($data, $model, $pk, false);

    $sets  = $this->sqlSets($data, $model, true);
    $query = sprintf("UPDATE %s SET %s WHERE %s = :%s",
      self::table($model), implode(', ', $sets),
      $pk, $pk
    );

    $statement = $this->_db->prepare($query);
    self::pdoBind($statement, $model, $data, false);
    $this->execute($statement);
    return $data[$pk];
  }

  /**
   * Deletes a record
   *
   * @param string $model
   * @param mixed $id
   * @param null|mixed $rev
   * @return bool|mixed
   */
  public function devour($model, $id, $rev = null) {
    $query = sprintf("DELETE FROM %s WHERE %s = ?",
      self::table($model), self::pk($model)
    );

    $statement = $this->_db->prepare($query);
    $statement->bindValue(1, $id);
    if ($this->execute($statement)) {
      return $id;
    }
    return false;
  }

  /**
   * @param string $model
   * @return mixed
   */
  public function truncate($model) {
//    $query    = sprintf("TRUNCATE %s RESTART IDENTITY CASCADE", implode(', ', self::tables(array($models))));
    $query    = sprintf("DELETE FROM %s", self::table($model));
    $affected = $this->execute($this->_db->prepare($query));
    //      throw new \Exception("Truncate test run");
    return $affected;
  }

// Use only when recreating data from dom on rdb
//  public function persist($model, array $data, $sequence = false) {
//    $row = $this->locate($model, $data[self::pk($model)]);
//    if (empty($row)) {
//      return $this->insert($model, $data, $sequence);
//    } else {
//      return $this->update($model, $data, $sequence);
//    }
//  }

  /**
   * @param string $model
   * @param array $data
   * @return mixed
   */
  public function kill($model, array $data) {
    $pk = self::pk($model);
    if (isset($data[$pk])) {
      return $this->devour($model, $data[$pk]);
    }
    $sets      = $this->sqlSets($data);
    $query     = sprintf("DELETE FROM %s WHERE (%s)",
      self::table($model), implode(') AND (', $sets)
    );
    $statement = $this->_db->prepare($query);
    return $this->execute($statement);
  }

  public function copy($model, $srcID, $sequence = null) {
    throw new DBALException(__METHOD__ . ' not implemented');
  }

  /********************************************************************************************************************/

  public function attachOne($id, $parentField, AbstractEntity &$child, $childSequence = false, $type = false) {
    return parent::attachOne($id, $parentField, $child, $childSequence, AbstractDBAL::P_RDB);
  }

  public function detachChilds($model, $parentPK, $parentID) {
    $query = sprintf("DELETE FROM %s WHERE %s = ?",
      self::table($model), $parentPK
    );

    $statement = $this->_db->prepare($query);
    $statement->bindValue(1, $parentID);
    if ($this->execute($statement)) {
      return $parentID;
    }
    return false;

  }

  public function parent($model, $childPK, $childID) {
    $rows = $this->select($model, array($childPK => $childID));
    if (empty($rows)) {
      return null;
    }
    return reset($rows);
  }

  /********************************************************************************************************************/

  public function childs($model, $parentPK, $parentID) {
    return $this->select($model, array($parentPK => $parentID));
  }

  /********************************************************************************************************************/

  public function linkAll($modelL, $modelR, $idL, $ids) {
    if (empty($ids)) {
      return $ids;
    }

    $this->unlinkAll($modelL, $modelR, $idL);

    $pdo = $this->pdo();

    $links = array();
    foreach ($ids as &$id) {
      $links[] = sprintf('%s, %s', $pdo->quote($idL), $pdo->quote($id));
      $id      = array($idL, $id);
    }
    $query     = sprintf("INSERT INTO %s (%s, %s) VALUES (%s)",
      self::link($modelL, $modelR), self::pk($modelL), self::pk($modelR), implode('),(', $links)
    );
    $statement = $this->_db->prepare($query);
    $this->execute($statement);

    return $ids;
  }

  public function linkOne($modelL, $modelR, $idL, $idR) {
    $this->unlinkOne($modelL, $modelR, $idL, $idR);
    $query     = sprintf("INSERT INTO %s (%s, %s) VALUES (?, ?)",
      self::link($modelL, $modelR), self::pk($modelL), self::pk($modelR)
    );
    $statement = $this->_db->prepare($query);
    $statement->bindValue(1, $idL);
    $statement->bindValue(2, $idR);
    $this->execute($statement);
    return array($idL, $idR);
  }

  public function unlinkAll($modelL, $modelR, $idL) {
    $query     = sprintf("DELETE FROM %s WHERE %s = ?",
      self::link($modelL, $modelR), self::pk($modelL)
    );
    $statement = $this->_db->prepare($query);
    $statement->bindValue(1, $idL);
    return $this->execute($statement);
  }

  public function unlinkOne($modelL, $modelR, $idL, $idR) {
    $query     = sprintf("DELETE FROM %s WHERE %s = ? AND %s = ?",
      self::link($modelL, $modelR), self::pk($modelL), self::pk($modelR)
    );
    $statement = $this->_db->prepare($query);
    $statement->bindValue(1, $idL);
    $statement->bindValue(2, $idR);
    return $this->execute($statement);
  }

  public function joints($modelL, $modelR, $idL) {
    $pkL = self::pk($modelL);

    $query = sprintf("SELECT %s FROM %s WHERE (%s = :%s)",
      self::pk($modelR), self::link($modelL, $modelR), $pkL, $pkL
    );

    $statement = $this->_db->prepare($query);
    self::pdoBind($statement, null, array($pkL => $idL));
    $this->execute($statement);

    return $statement->fetchAll(PDO::FETCH_COLUMN);
  }

  public function nullify($model, $idField) {
    $sql = sprintf("UPDATE %s SET %s = NULL ", RDB::table($model), $idField);

    $statement = $this->pdo()->prepare($sql);
    return $this->execute($statement);
  }

}
