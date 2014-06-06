<?php
namespace BeatsBundle\DBAL;

use BeatsBundle\DBAL\XML\BeatsXMLElement;
use BeatsBundle\Exception\DBALException;

class XML extends AbstractDB {

  const VERSION = 'v0.1';

  const DEFAULT_HOME  = '/beats/dbal';
  const DEFAULT_CLASS = 'BeatsBundle\\DBAL\\XML\\BeatsXMLElement';

  private $_home;
  private $_root = array();
  private $_file = array();
  protected $_class = self::DEFAULT_CLASS;

  /**
   * @param array $config
   */
  public function __construct(array $config) {
    $this->_config = $config;

    $class = $this->_config['class'];
    if (!class_exists($class)) {
      throw new DBALException("Class doesn't exist: $class");
    }
    $this->_class = $class;

    $home = $this->_config['home'];
    if (!is_dir($home)) {
      throw new DBALException("Home dir doesn't exist: $home");
    }
    $this->_home = $home;
  }

  /**
   * @param $model
   *
   * @return BeatsXMLElement
   * @throws \BeatsBundle\Exception\DBALException
   */
  protected function _xml($model) {
    if (empty($this->_root[$model])) {
      if (empty($this->_file[$model])) {
        $this->_file[$model] = implode(DIRECTORY_SEPARATOR, array($this->_home, "$model.xml"));
      }
      if (is_readable($this->_file[$model])) {
        $root = $this->_load($model);
      } else {
        $root = $this->_save($model, simplexml_load_string("<root/>", $this->_class));
      }
      $this->_root[$model] = $root;
    }
    return $this->_root[$model];
  }

  /**
   * @param string          $model
   * @param array           $data
   * @param BeatsXMLElement $root
   *
   * @return BeatsXMLElement
   */
  protected function _build($model, BeatsXMLElement &$root, array $data) {
    $node = $root->addChild($model);
    foreach ($data as $key => $value) {
      if (is_array($value) && !empty($value)) {
        foreach ($value as $val) {
          $node->addChild($key, $val);
        }
      } else {
        $node->addAttribute($key, $value);
      }
    }
    return $node;
  }

  /**
   * @param string          $model
   * @param BeatsXMLElement $node
   *
   * @return array
   */
  protected function _parse($model, BeatsXMLElement $node) {
    $data = (array)$node;
    $attr = $data['@attributes'];
    unset($data['@attributes']);
    $data = array_merge($attr, $data);
    return $data;
  }

  /**
   * @param string          $model
   * @param BeatsXMLElement $root
   *
   * @return BeatsXMLElement
   * @throws \BeatsBundle\Exception\DBALException
   */
  protected function _save($model, BeatsXMLElement $root) {
    $file = $this->_file[$model];
    if (!$root->saveXML($file)) {
      throw new DBALException("File not writable: $file");
    }
    return $root;
  }


  protected function _locate(BeatsXMLElement $root, $model, $id) {
    $xpath = sprintf("/root/%s[@%s='%s']", $model, self::pk($model), $id);
    $node  = $root->xpathOne($xpath);
    return empty($node) ? null : $node;
  }

  /**
   * @param string $model
   *
   * @return BeatsXMLElement
   * @throws \BeatsBundle\Exception\DBALException
   */
  protected function _load($model) {
    $file = $this->_file[$model];
    $root = simplexml_load_file($file, $this->_class);
    if (!$root instanceof BeatsXMLElement) {
      throw new DBALException("File not XML parseable: {$this->_class}@$file");
    }
    return $root;
  }


  public function version() {
    return self::VERSION;
  }

  /**
   * Locates an entity by it ID
   *
   * @param string $model
   * @param mixed  $id
   *
   * @return object|mixed
   */
  public function locate($model, $id) {
    $root = $this->_xml($model);
    $node = $this->_locate($root, $model, $id);
    return empty($node) ? null : $this->_parse($model, $node);
  }

  /**
   * @param       $model
   * @param array $where
   * @param array $order
   * @param int   $limit
   * @param int   $offset
   * @param bool  $equal
   *
   * @return object[]|mixed
   */
  public function select($model, array $where = array(), array $order = array(), $limit = 0, $offset = 0, $equal = true) {
    throw new DBALException('Not implemented: ' . __METHOD__);
  }

  /**
   * Insert a new record
   *
   * @param string $model
   * @param array  $data
   * @param bool   $sequence
   *
   * @return mixed The primary key
   */
  public function insert($model, array $data, $sequence = false) {
    $pk = self::pk($model);
    if (empty($data[$pk])) {
      $data[$pk] = self::uuid();
    }
    $id   = $data[$pk];
    $root = $this->_xml($model);
    $node = $this->_locate($root, $model, $id);
    if ($node) {
      return $this->update($model, $data);
    }
    $this->_setup($data, $model, $pk, true);
    $this->_build($model, $root, $data);
    $root = $this->_save($model, $root);
    return empty($root) ? false : $id;
  }

  /**
   * @param string $model
   * @param array  $data
   *
   * @internal param mixed $id
   * @return mixed The primary key
   */
  public function update($model, array $data) {
    $pk = self::pk($model);
    if (empty($data[$pk])) {
      return $this->insert($model, $data);
    }
    $id   = $data[$pk];
    $root = $this->_xml($model);
    $node = $this->_locate($root, $model, $id);
    unset($node);
    $this->_setup($data, $model, $pk, true);
    $this->_build($model, $root, $data);
    $root = $this->_save($model, $root);
    return empty($root) ? false : $id;
  }

  /**
   * @param string     $model
   * @param mixed      $id
   * @param null|mixed $rev
   *
   * @return mixed
   */
  public function devour($model, $id, $rev = null) {
    $pk = self::pk($model);
    if (empty($data[$pk])) {
      return false;
    }
    $id   = $data[$pk];
    $root = $this->_xml($model);
    $node = $this->_locate($root, $model, $id);
    unset($node);
    $root = $this->_save($model, $root);
    return empty($root) ? false : $id;
  }

  /**
   * @param string $model
   *
   * @return mixed
   */
  public function truncate($model) {
    $count               = count($this->_xml($model)->$model);
    $this->_root[$model] = $this->_save($model, simplexml_load_string("<root/>", $this->_class));
    return $count;
  }

  /**
   * @param string $model
   * @param array  $data
   *
   * @return mixed
   */
  public function kill($model, array $data) {
    $pk = self::pk($model);
    if (isset($data[$pk])) {
      $id = $data[$pk];
      return $this->devour($model, $id);
    }
    throw new DBALException("XML doesn't support multiple document delete");
  }

  public function copy($model, $srcID, $sequence = false) {
    throw new DBALException('Not implemented: ' . __METHOD__);
  }

}