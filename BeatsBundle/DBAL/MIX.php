<?php
namespace BeatsBundle\DBAL;

use BeatsBundle\Exception\DBALException as Exception;

class MIX extends AbstractDB {

  /**
   * @var RDB
   */
  private $_rdb;

  /**
   * @var DOM
   */
  private $_dom;


  static public function eliminate(RDB $rdb, DOM $dom, $model) {
    $affected = $rdb->truncate($model);
    if ($affected === false) {
      throw new Exception("Couldn't truncate MIX->RDB [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
    }
    $affected = $dom->truncate($model);
    if ($affected === false) {
      throw new Exception("Couldn't truncate MIX->DOM [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
    }
  }


  public function __construct(DOM $dom, RDB $rdb) {
    $this->_rdb = $rdb;
    $this->_dom = $dom;
  }


  /**
   * @return RDB
   */
  public function rdb() {
    return $this->_rdb;
  }

  /**
   * @return DOM
   */
  public function dom() {
    return $this->_dom;
  }

  /******************************************************/

  /**
   * Returns the current version of the DB layer
   * @return string
   */
  public function version() {
    return (object)array(
      'rdb' => $this->rdb()->version(),
      'dom' => $this->dom()->version(),
    );
  }


  public function inTransaction() {
    return $this->rdb()->inTransaction();
  }

  public function start() {
    return $this->rdb()->start();
  }

  public function commit() {
    return $this->rdb()->commit();
  }

  public function revert() {
    return $this->rdb()->revert();
  }

  public function transaction(\Closure $callback) {
    $args = func_get_args();
    array_splice($args, 1, 0, array($this->dom()));
    return call_user_func_array(array($this->rdb(), __FUNCTION__), $args);
  }


  /**
   * Locates an entity by it ID
   * @param string $model
   * @param mixed $id
   * @return object
   */
  public function locate($model, $id) {
    return $this->_dom->locate($model, $id);
  }


  public function select($model, array $where = array(), array $order = array(), $limit = 0, $offset = 0, $equal = true) {
    return $this->_dom->select($model, $where, $order, $limit, $offset, $equal);
  }

  /**
   * @param string $model
   * @param array $data
   * @param bool $sequence
   * @return mixed
   * @throws Exception
   */
  public function insert($model, array $data, $sequence = false) {
    return $this->_rdb->transaction(function (RDB $rdb, DOM $dom, $model, array $data, $sequence) {
      $pk = self::pk($model);
      if (!$sequence) {
        $data[$pk] = self::uuid();
      }
      AbstractDB::created($data);

      $id = $rdb->insert($model, $data);
      if ($id === false) {
        throw new Exception("Couldn't insert data into MIX->RDB [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
      }
      $data[$pk] = $id;
      $_id       = $dom->insert($model, $data);
      if ($_id === false) {
        throw new Exception("Couldn't insert data into MIX->DOM [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
      }
      return $id;
    }, $this->_dom, $model, $data, $sequence);
  }

  /**
   * @param string $model
   * @param array $data
   * @return bool|mixed
   * @throws Exception
   * @internal param mixed $id
   * @internal param mixed $
   * @throws Exception
   */
  public function update($model, array $data) {
    return $this->_rdb->transaction(function (RDB $rdb, DOM $dom, $model, array $data) {
      $affected = $rdb->update($model, $data);
      if ($affected === false) {
        throw new Exception("Couldn't update data into MIX->RDB [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
      }
      $affected = $dom->update($model, $data);
      if ($affected === false) {
        throw new Exception("Couldn't update data into MIX->DOM [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
      }
      return $affected;
    }, $this->_dom, $model, $data);
  }

  /**
   * @param string $model
   * @param mixed $id
   * @param null|mixed $rev
   * @return mixed
   * @throws \Exception
   */
  public function devour($model, $id, $rev = null) {
    return $this->_rdb->transaction(function (RDB $rdb, DOM $dom, $model, $id, $rev) {
      $affected = $rdb->devour($model, $id, $rev);
      if ($affected === false) {
        throw new Exception("Couldn't delete [$id:$rev] from MIX->RDB [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
      }
      if (empty($rev)) {
        $rev = $dom->revision($model, $id);
      }
      $affected = $dom->devour($model, $id, $rev);
      if ($affected === false) {
        throw new Exception("Couldn't delete [$id:$rev] from MIX->DOM [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
      }
      return $affected;
    }, $this->_dom, $model, $id, $rev);
  }

  /**
   * @param string $model
   * @return mixed
   * @throws Exception
   */
  public function truncate($model) {
    return $this->_rdb->transaction(function (RDB $rdb, DOM $dom, $model) {
      $affected = $rdb->truncate($model);
      if ($affected === false) {
        throw new Exception("Couldn't truncate MIX->RDB [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
      }
      $affected = $dom->truncate($model);
      if ($affected === false) {
        throw new Exception("Couldn't truncate MIX->DOM [$model]"); // 403 Forbidden header('X-Reason', $ex->getMessage());
      }
//      throw new \Exception("Truncate test run");
      return $affected;
    }, $this->_dom, $model);
  }

  /**
   * @param string $model
   * @param array $data
   * @return mixed
   * @throws Exception
   */
  public function kill($model, array $data) {
    $pk = self::pk($model);
    if (isset($data[$pk])) {
      return $this->devour($model, $data[$pk]);
    } else {
      throw new Exception("MIX->DOM doesn't support multiple document delete without a view");
    }
  }

  public function copy($model, $srcID, $sequence = false) {
    try {
      $dstID = $this->dom()->copy($model, $srcID, $sequence);
      $data  = $this->dom()->locate($model, $dstID);
      return $this->save($model, $data, $sequence);
    } catch (\Exception $ex) {
      if (empty($dstID)) {
        $dstID = null;
      } else {
        $this->dom()->devour($model, $dstID);
      }
      throw new Exception("Failed to copy document [src: $srcID, dst: $dstID");
    }
  }

}
