<?php
namespace BeatsBundle\DBAL;

use BeatsBundle\Entity\AbstractEntity;
use BeatsBundle\Exception\DBALException;
use BeatsBundle\Helper\UTC;
use Rug\Exception\RugException;
use Rug\Rug;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Implement cURL REST CouchDB proxy ...
 */
class DOM extends AbstractDB {

  const DOM_ID    = '_id';
  const DOM_REV   = '_rev';
  const DOM_ATT   = '_attachments';
  const RDB_MODEL = '$model';

//  /**
//   * @var Sag
//   */
//  public $_db;

  /**
   * @var Rug
   */
  protected $_rug;

  /**
   * @var array
   */
  protected $_config;

  /**
   * @param array $config
   */
  public function __construct(array $config) {
    $this->_config = $config;
//    $this->_db     = new Sag($config['host'], $config['port']);
////    $this->_db->setHTTPAdapter(Sag::$HTTP_CURL);
//    $this->_db->setHTTPAdapter(Sag::$HTTP_NATIVE_SOCKETS);
//    $this->_db->setDatabase($config['name']);
    $this->_rug = new Rug($config);
  }

  protected function _table($model) {
    return $this->_rug->db()->design('tables')->view(self::collection($model))->fetchAll()->all();
//    $rows = $this->_db->get('_design/tables/_view/' . self::collection($model) . '?' . $query)->body->rows;
//    return array_map(function ($row) {
//      return $row->value;
//    }, $rows);
  }

  /**
   * @return Rug
   */
  public function rug() {
    return $this->_rug;
  }

  /**
   * Returns revision for a specific document
   * @param string $model
   * @param string $id
   * @param mixed  $_id
   * @return mixed
   * @throws DBALException
   */
  public function revision($model, $id, &$_id = null) {
    try {
      $_id = self::domID($model, $id);

      return $this->_rug->db()->doc($_id)->rev();
    } catch (RugException $ex) {
      throw new DBALException("Document not found [$id]");
    }
//    $doc = $this->locate($model, $id);
//    if (empty($doc)) {
//      throw new DBALException("Document not found [$id]");
//    }
//    $_id = $doc->_id;
//    return $doc->_rev;
  }

  /******************************************************/

  protected function _setup(array &$data, $model, $pk, $insert = true, $sequence = false) {
    $data[self::RDB_MODEL] = $model;
    if ($insert) {
      $data[self::DOM_ID] = self::domID($model, isset($data[$pk]) ? $data[$pk] : null);
      unset($data[self::DOM_REV]);
    } else {

    }
    if (empty($data[self::DOM_ATT])) {
      unset($data[self::DOM_ATT]);
    }
    $data[$pk] = self::rdbID($model, $data[self::DOM_ID]);

    return parent::_setup($data, $model, $pk, $insert, $sequence);
  }

  /**
   * Returns the current version of the DB layer
   * @return string
   */
  public function version() {
    return $this->_rug->server()->version();
//    return $this->_db->get('/')->headers->Server;
  }

  /******************************************************/

  public function inTransaction() {

    // LATER: Implement inTransaction() method.
  }

  public function start() {
    // LATER: Implement start() method.
  }

  public function commit() {
    // LATER: Implement commit() method.
  }

  public function revert() {
    // LATER: Implement revert() method.
  }

  /******************************************************/

  /**
   * @param $id
   * @param $model
   * @return object|mixed
   */
  public function locate($model, $id) {
    try {
      return $this->_rug->db()->find(self::domID($model, $id));
    } catch (RugException $ex) {
      return false;
    }
//    try {
//      return $this->_db->get(self::domID($model, $id))->body;
//    } catch (\SagCouchException $ex) {
//      return false;
//    }
  }


  public function select($model, array $where = array(), array $order = array(), $limit = 0, $offset = 0, $equal = true) {
    if (!empty($order)) {
      throw new DBALException("DOM doesn't support ORDER BY clauses");
    }

    return $this->_table($model);
  }

  /**
   * @param string $model
   * @param array  $data
   * @param bool   $sequence
   * @return mixed
   */
  public function insert($model, array $data, $sequence = false) {
    if (isset($data[self::DOM_ID])) {
      return $this->update($model, $data);
    }
    $pk = self::pk($model);
    $this->_setup($data, $model, $pk, true, $sequence);
    $_id = $this->_rug->db()->save($data)->id;

//    $_id = $this->_db->put($data[self::DOM_ID], $data)->body->id;
    return self::rdbID($model, $_id);
  }

  /**
   * @param string $model
   * @param array  $data
   * @internal param mixed $id
   * @return mixed
   */
  public function update($model, array $data) {
    $pk = self::pk($model);
    if (empty($data[self::DOM_ID])) {
      $doc = $this->locate($model, $data[$pk]);
      if (empty($doc)) {
        return $this->insert($model, $data, false);
      }
      $data[self::DOM_REV] = $doc->_rev;
      $data[self::DOM_ID]  = $doc->_id;
    } elseif (empty($data[self::DOM_REV])) {
      $data[self::DOM_REV] = $this->revision($model, $data[$pk]);
    }
    $this->_setup($data, $model, $pk, false);

    $_id = $this->_rug->db()->save($data)->id;

//    $_id = $this->_db->put($data[self::DOM_ID], $data)->body->id;
    return self::rdbID($model, $_id);
  }

  /**
   * @param string     $model
   * @param mixed      $id
   * @param null|mixed $rev
   * @return mixed
   */
  public function devour($model, $id, $rev = null) {
    try {
      return $this->_rug->db()->kill(self::domID($model, $id))->id;
    } catch (RugException $ex) {
      return false;
    }
//    if (empty($rev)) {
//      $rev = $this->revision($model, $id);
//    }
//    if ($this->_db->delete(self::domID($model, $id), $rev)->body->ok) {
//      return $id;
//    }
//    return false;
  }

  /**
   * Kills all of documents for specific (view) model (as truncate in SQL)
   * @param string $model
   * @return mixed
   */
  public function truncate($model) {
    $documents = $this->_table($model);

    return $this->killAll($model, $documents);
  }

  /**
   * @param string $model
   * @param array  $data
   * @return mixed
   * @throws DBALException
   */
  public function kill($model, array $data) {
    $pk = self::pk($model);
    if (isset($data[$pk])) {
      $id = $data[$pk];
      if (isset($data[self::DOM_REV])) {
        $rev = $data[self::DOM_REV];
      } else {
        $rev = $this->revision($model, $id);
      }

      return $this->devour($model, $id, $rev);
    }
    throw new DBALException("DOM doesn't support multiple document delete without a view");
  }

  public function copy($model, $srcID, $sequence = null) {
    $_id = $this->_rug->db()->doc(self::domID($model, $srcID))->copy(self::domID($model))->id;
//    $_id = $this->_db->copy(self::domID($model, $srcID), self::domID($model))->body->id;

    $dstID = self::rdbID($model, $_id);
    $data  = (array)$this->locate($model, $dstID);

    $data[self::pk($model)] = $dstID;
    $data[self::CREATED]    = UTC::toTimestamp();

    $this->_rug->db()->save($data);

//    $this->_db->put($data[self::DOM_ID], $data);
    return $dstID;
  }

  public function attachOne($id, $parentField, AbstractEntity &$child, $childSequence = false, $type = false) {
    return parent::attachOne($id, $parentField, $child, $childSequence, AbstractDBAL::P_DOM);
  }

  public function killAll($model, array $documents) {
//    return count($documents);
    $killed = 0;
    foreach ($documents as $document) {
      $pk            = self::pk($model);
      $document->$pk = self::rdbID($model, $document->_id);
      /** @noinspection PhpUnusedLocalVariableInspection */
      $id = $this->kill($model, (array)$document);
      $killed++;
    }

    return $killed;
  }

  /********************************************************************************************************************/

  /**
   * @param $type
   * @return \Rug\Gateway\Database\Document\DesignGateway
   */
  public function views($type) {
    return $this->_rug->design($type);
  }

  /**
   * @param $type
   * @param $name
   * @return \Rug\Gateway\Database\Document\Design\ViewGateway
   */
  public function view($type, $name) {
    return $this->views($type)->view($name);
  }

//  public function view($type, $name, $parameters = array(), $single = false, $raw = false) {
//    if (!is_array($parameters)) {
//      $parameters = array('key' => $parameters);
//    }
//    $args = array();
//    foreach ($parameters as $key => $val) {
//      $args[] = urlencode($key) . '=' . json_encode($val);
//    }
//    return $this->_view($type, $name, implode('&', $args), $single, $raw);
//  }

//  /**
//   * @param string $type
//   * @param string $name
//   * @param string $query
//   * @param bool $single
//   * @param bool $raw
//   * @return array
//   */
//  public function _view($type, $name, $query, $single = false, $raw = false) {
//    $rows = $this->_db->get('_design/' . $type . '/_view/' . $name . '?' . $query)->body->rows;
//    if (empty($rows)) {
//      return $single ? null : array();
//    }
//    if ($single) {
//      $row = reset($rows);
//      return $raw ? $row : $row->value;
//    }
//    return $raw ? $rows : array_map(function ($row) {
//      return $row->value;
//    }, $rows);
//  }

  /********************************************************************************************************************/

  public function open($model, $id, $name, $mode = 'r') {
    return $this->_rug->db()->doc(self::domID($model, $id))->attachmentFile($name)->openFile($mode);
//    $sag  = $this->_db;
//    $link = sprintf('%s://%s:%d/%s/%s/%s',
//      $sag->usingSSL() ? 'https' : 'http', $this->_config['host'], $this->_config['port'],
//      $sag->currentDatabase(), self::domID($model, $id), $name
//    );
//    return fopen($link, $mode);
  }

  public function store($model, $id, File $file, $name = null, $rev = null) {
    if (empty($name)) {
      $name = $file->getBasename();
    }

    return $this->_rug->db()->doc(self::domID($model, $id))->attach($name, $file, $rev);
//    if (empty($rev)) {
//      $rev = $this->revision($model, $id, $domID);
//    } else {
//      $domID = $this->domID($model, $id);
//    }
//    if (empty($name)) {
//      $name = $file->getBasename();
//    }
//    $response = $this->_db->setAttachment($name,
//      file_get_contents($file->getRealPath()), $file->getMimeType(),
//      $domID, $rev
//    );
//    return $response->body;
  }

  /********************************************************************************************************************/

  public function killDependencies($key, $type, $name = 'dependencies') {
    // Don't forget to emit(doc.id_xxx, doc.$model), where doc.id_xxx is the field that holds the $key param
    $rows = $this->view($type, $name)->fetchKey($key)->all(true);
    $affected = array();
    foreach ($rows as $row) {
      $affected[] = $this->devour($row->value, DOM::rdbID($row->value, $row->id));
    }
    return $affected;
  }

  /********************************************************************************************************************/

}
