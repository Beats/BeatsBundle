<?php
namespace BeatsBundle\DBAL;

use BeatsBundle\Entity\AbstractEntity;
use BeatsBundle\Exception\DBALException;
use PDO;
use PDOStatement;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface as Templater;

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

  /**
   * @var Templater
   */
  private $_templater;

  /**
   * @return Templater
   */
  final protected function _templater() {
    return $this->_templater;
  }

  /********************************************************************************************************************/

  public function __construct($config, Templater $templater) {
    // LATER: Read the configuration the correct way;
    $connection_string = sprintf(
      "%s:host=%s;port=%d;dbname=%s;user=%s;password=%s",
      $config['drvr'],
      $config['host'],
      $config['port'],
      $config['name'],
      $config['user'],
      $config['pass']
    );

    $this->_db = new PDO($connection_string);

    $this->_templater = $templater;
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
   * @param      $data
   * @param      $model
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
      return array_map(
        function ($field) {
          return "$field = :$field";
        }, $fields
      );
    } else {
      return array_map(
        function ($field) {
          return "$field <> :$field";
        }, $fields
      );
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
   * @param       $model
   * @param array $where
   * @param array $order
   * @param int   $limit
   * @param int   $offset
   * @param bool  $equal
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

  /********************************************************************************************************************/

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
   * @param mixed  $id
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
   * @param array  $parent
   * @param array  $childs
   * @param array  $joints
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
    $query  = sprintf(
      "INSERT INTO %s(%s) VALUES (:%s)",
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
    $query = sprintf(
      "UPDATE %s SET %s WHERE %s = :%s",
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
   * @param string     $model
   * @param mixed      $id
   * @param null|mixed $rev
   * @return bool|mixed
   */
  public function devour($model, $id, $rev = null) {
    $query = sprintf(
      "DELETE FROM %s WHERE %s = ?",
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
   * @param array  $data
   * @return mixed
   */
  public function kill($model, array $data) {
    $pk = self::pk($model);
    if (isset($data[$pk])) {
      return $this->devour($model, $data[$pk]);
    }
    $sets      = $this->sqlSets($data);
    $query     = sprintf(
      "DELETE FROM %s WHERE (%s)",
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
    $query = sprintf(
      "DELETE FROM %s WHERE %s = ?",
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

  public function linkAll($modelL, $modelR, $idL, $ids, $pkR = null) {
    if (empty($ids)) {
      return $ids;
    }
    if (empty($pkR)) {
      $pkR = self::pk($modelR);
    }

    $this->unlinkAll($modelL, $modelR, $idL, $pkR);

    $pdo = $this->pdo();

    $links = array();
    foreach ($ids as &$id) {
      $links[] = sprintf('%s, %s', $pdo->quote($idL), $pdo->quote($id));
      $id      = array($idL, $id);
    }
    $query     = sprintf(
      "INSERT INTO %s (%s, %s) VALUES (%s)",
      self::link($modelL, $modelR), self::pk($modelL), $pkR, implode('),(', $links)
    );
    $statement = $this->_db->prepare($query);
    $this->execute($statement);

    return $ids;
  }

  public function linkOne($modelL, $modelR, $idL, $idR, $pkR = null) {
    if (empty($pkR)) {
      $pkR = self::pk($modelR);
    }

    $this->unlinkOne($modelL, $modelR, $idL, $idR, $pkR);
    $query     = sprintf(
      "INSERT INTO %s (%s, %s) VALUES (?, ?)",
      self::link($modelL, $modelR), self::pk($modelL), $pkR
    );
    $statement = $this->_db->prepare($query);
    $statement->bindValue(1, $idL);
    $statement->bindValue(2, $idR);
    $this->execute($statement);

    return array($idL, $idR);
  }

  public function unlinkAll($modelL, $modelR, $idL) {
    $query     = sprintf(
      "DELETE FROM %s WHERE %s = ?",
      self::link($modelL, $modelR), self::pk($modelL)
    );
    $statement = $this->_db->prepare($query);
    $statement->bindValue(1, $idL);

    return $this->execute($statement);
  }

  public function unlinkOne($modelL, $modelR, $idL, $idR, $pkR = null) {
    if (empty($pkR)) {
      $pkR = self::pk($modelR);
    }

    $query     = sprintf(
      "DELETE FROM %s WHERE %s = ? AND %s = ?",
      self::link($modelL, $modelR), self::pk($modelL), $pkR
    );
    $statement = $this->_db->prepare($query);
    $statement->bindValue(1, $idL);
    $statement->bindValue(2, $idR);

    return $this->execute($statement);
  }

  public function joints($modelL, $modelR, $idL, $pkR = null) {
    $pkL = self::pk($modelL);
    if (empty($pkR)) {
      $pkR = self::pk($modelR);
    }

    $query = sprintf(
      "SELECT %s FROM %s WHERE (%s = :%s)",
      $pkR, self::link($modelL, $modelR), $pkL, $pkL
    );

    $statement = $this->_db->prepare($query);
    self::pdoBind($statement, null, array($pkL => $idL));
    $this->execute($statement);

    return $statement->fetchAll(PDO::FETCH_COLUMN);
  }

  /********************************************************************************************************************/

  public function nullify($model, $field, $id = null) {
    if (empty($id)) {
      $sql       = sprintf("UPDATE %s SET %s = NULL", self::table($model), $field);
      $statement = $this->pdo()->prepare($sql);
    } else {
      $sql       = sprintf("UPDATE %s SET %s = NULL WHERE %s = ? ", self::table($model), $field, self::pk($model));
      $statement = $this->pdo()->prepare($sql);
      $statement->bindValue(1, $id);
    }

    return $this->execute($statement);
  }

  public function count($model, $id = null, $pk = null) {
    if (empty($pk)) {
      $pk = self::pk($model);
    }
    if (is_array($model)) {
      $table = self::link($model[0], $model[1]);
    } else {
      $table = self::table($model);
    }
    if (empty($id)) {
      $query = sprintf(
        "SELECT COUNT(*) FROM %s",
        $table
      );
    } else {
      $query = sprintf(
        "SELECT COUNT(*) FROM %s WHERE (%s = :%s)",
        $table, $pk, $pk
      );
    }

    $statement = $this->_db->prepare($query);
    if (!empty($id)) {
      RDB::pdoBind($statement, null, array($pk => $id));
    }
    $this->execute($statement);

    return current($statement->fetchAll(PDO::FETCH_COLUMN));
  }

  /********************************************************************************************************************/

  protected function _template($name) {
    return "$name.sql.twig";
  }

  /**
   * @param string $template
   * @param array  $params
   * @return string
   */
  public function sql($template, array $params = array()) {
    return $this->_templater()->render($this->_template($template), $params);
  }

  public function sqlFetch($template, $tplParams, $sqlParams = array(), $one = false, $style = PDO::FETCH_OBJ) {
    $sql = $this->sql($template, $tplParams);

    $statement = $this->_db->prepare($sql);
    self::pdoBind($statement, null, $sqlParams);
    $this->execute($statement);

    return $one
      ? $statement->fetch($style)
      : $statement->fetchAll($style);
  }

  public function fetchIDs($sql, $params, array $fields = array(), &$aggregations = false) {
    $statement = $this->_db->prepare($sql);
    self::pdoBind($statement, null, $params);
    $this->execute($statement);

    if ($aggregations === false) {
      $ids = $statement->fetchAll(\PDO::FETCH_COLUMN);
    } else {
      $ids = $statement->fetchAll(
        PDO::FETCH_FUNC,
        function ($id) use ($fields, &$aggregations) {
          $aggregations[$id] = array_combine($fields, func_get_args());
          array_shift($aggregations[$id]);
          return $id;
        }
      );
    }
    return empty($ids) ? array() : $ids;
  }

  public function filterIDs($model,
                            $params = array(),
                            $fields = array(),
                            $links = array(),
                            $where = array(),
                            $having = array(),
                            $group = array(),
                            $order = array(),
                            $limit = 0, $offset = 0,
                            $distinct = true,
                            &$aggregations

  ) {

    $entity = (object)array(
      'table' => RDB::table($model),
      'pk'    => RDB::pk($model),
    );
    $fields = array('_id' => sprintf(
        "'%s_' || %s.%s", self::collection($model), $entity->table, $entity->pk
      )) + $fields;

    $page = empty($limit) ? null : (object)array('limit' => abs($limit), 'offset' => empty($offset) ? 0 : abs($offset));

    $sql = $this->sql(
      "BeatsBundle:sql:filter", array(
        'distinct' => $distinct,
        'model'    => $model,
        'fields'   => $fields,
        'entity'   => $entity,
        'links'    => $links,
        'where'    => $where,
        'having'   => $having,
        'group'    => $group,
        'order'    => $order,
        'page'     => $page,
      )
    );
//    ini_set('xdebug.var_display_max_data', -1);
//    var_dump($sql, $params);

    return $this->fetchIDs($sql, $params, array_keys($fields), $aggregations);
  }

  /********************************************************************************************************************/

}
