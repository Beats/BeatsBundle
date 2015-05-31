<?php
namespace BeatsBundle\Entity;

use BeatsBundle\DBAL\AbstractDB;
use BeatsBundle\DBAL\AbstractDBAL;
use BeatsBundle\Exception\Exception;

/**
 * Should  extends \ArrayObject for Validation (Removed for further development without validation)
 */
class AbstractEntity implements \IteratorAggregate {

  /**
   * @var string
   */
  static protected $_model;

  /**
   * @var string[]
   */
  static protected $_parent = array();

  /**
   * @var string[]
   */
  static protected $_childs = array();

  /**
   * @var string[]
   */
  static protected $_joints = array();

  /**
   * @var string[]
   */
  static protected $_others = array();

  /**
   * @var string[]
   */
  static protected $_equals = array();

  /**
   * @return string
   */
  static public function getPK() {
    return AbstractDB::pk(static::getModel());
  }

  /**
   * @return string
   */
  static public function getModel() {
    return static::$_model;
  }

  /**
   * @return array
   */
  static public function getParent() {
    return static::$_parent;
  }

  /**
   * @return array
   */
  static public function getChilds() {
    return static::$_childs;
  }

  /**
   * @return array
   */
  static public function getJoints() {
    return static::$_joints;
  }

  /********************************************************************************************************************/

  static public function walk(array $array, $format) {
    return array_map(
      function ($value) use ($format) {
        return sprintf($format, $value);
      }, $array
    );
  }

  static public function fields($format = null, $array = true) {
    static $fields = array();
    if (empty($fields)) {
      $class = get_called_class();
      foreach (get_class_vars($class) as $field => $value) {
        if ($field[0] == '_'
          || isset(static::$_parent[$field])
          || isset(static::$_childs[$field])
          || isset(static::$_joints[$field])
          || isset(static::$_others[$field])
        ) {
          continue;
        }
        $fields[] = $field;
      }
    }
    if ($format) {
      $data = self::walk($fields, $format);
    } else {
      $data = $fields;
    }

    return $array ? $data : implode(',', $data);
  }

  /********************************************************************************************************************/

  /**
   * A helper method used for working with Entity enumerations
   * Enumerations are encoded as associative arrays of
   *  enum values as keys and
   *  enum labels as values.
   * If the $key argument evaluates to FALSE a vector of enum values is returned
   * If the $key argument is set to NULL the original associative array is returned
   * If the $key argument is an enumeration value, the enumeration label is returned
   * In all other cases the NULL is returned
   *
   * @param array     $associative A reference to an enumeration (associative array)
   * @param bool|null $enum        An enumeration value or false
   * @return array|null a label for a certain enumeration or a vector of enumeration values
   */
  static protected function _enumeration(array &$associative, $enum = null) {
    static $enumerations;
    if (empty($enum)) {
      if (empty($enumerations)) {
        $enumerations = array_keys($associative);
      }

      return is_null($enum) ? (array)$associative : $enumerations;
    }

    return isset($associative[$enum]) ? $associative[$enum] : null;
  }

  /********************************************************************************************************************/

  static protected function _mask($value, $mask) {
    return $mask & $value;
  }

  static protected function _flagged($value, $mask) {
    return self::_mask($value, $mask) == $mask;
  }

  static protected function _switch(&$value, $flag, $on = true) {
    return $on ? $value |= $flag : $value &= ~$flag;
  }

  static protected function _toggle(&$value, $flag) {
    return $value ^= $flag;
  }

  static protected function _isFlag($value) {
    return ($value && (($value & (~$value + 1)) == $value));
  }

  /********************************************************************************************************************/

  /**
   * @param string|null $field
   * @return string[]|string|null
   */
  static protected function _parent($field = null) {
    return self::_enumeration(static::$_parent, $field);
  }

  /**
   * @param string|null $field
   * @return string[]|string|null
   */
  static protected function _childs($field = null) {
    return self::_enumeration(static::$_childs, $field);
  }

  /**
   * @param string|null $field
   * @return string[]|string|null
   */
  static protected function _joints($field = null) {
    return self::_enumeration(static::$_joints, $field);
  }

  /**
   * @param string|null $field
   * @return string[]|string|null
   */
  static protected function _others($field = null) {
    return self::_enumeration(static::$_others, $field);
  }

  /**
   * @param array $values
   * @param       $class
   * @return AbstractEntity
   */
  static protected function _build(array $values, $class) {
    return new $class($values);
  }

  /********************************************************************************************************************/

  /**
   * @param $data
   * @return array
   */
  static public function fromString($data) {
    return json_decode($data, true);
  }

  /**
   * @param array $data
   * @return string
   */
  static public function toString(array $data) {
    return json_encode($data, JSON_PRETTY_PRINT);
  }

  /********************************************************************************************************************/

  /**
   * @param array|string|\stdClass $entity
   * @param bool                   $map
   * @throws Exception
   */
  public function __construct($entity = null, $map = true) {
    if (empty($entity)) {
      return;
    }
    if (is_string($entity)) {
      $entity = static::fromString($entity);
    } elseif (!is_array($entity)) {
      $class = get_class($entity);
      if ($class == "stdClass") {
        $entity = get_object_vars($entity);
      } else {
        throw new Exception("Invalid entity type: $class");
      }
    }
    $this->_rehydrate($entity, $map);
  }

  /**
   * Returns an array ready for RDB persistence
   * @param $type
   * @return array
   */
  public function _toDB($type) {
    return $this->_dehydrate($type != AbstractDBAL::P_RDB);
  }

  static private function _rehydrateEntity($data, $classParent) {
    return static::_build((array)$data, $classParent);
  }

  static private function _rehydrateChilds($data, $classChilds, $map = true) {
    $childs = array();
    $values = (array)$data;
    if ($map) {
      foreach ($values as $value) {
        $entity = static::_rehydrateEntity($value, $classChilds);
        $id     = $entity->getID();
        if (empty($id)) {
          $childs[] = $entity;
        } else {
          $childs[$id] = $entity;
        }
      }
    } else {
      foreach ($values as $value) {
        $childs[] = static::_rehydrateEntity($value, $classChilds);
      }
    }

    return $childs;
  }

  /**
   * @param array $entity
   * @param bool  $map
   * @return AbstractEntity
   */
  private function _rehydrate(array $entity, $map = true) {
    foreach ($entity as $field => $value) {
      $classParent = static::_parent($field);
      $classOthers = static::_others($field);
      $classChilds = static::_childs($field);
      if ($field[0] == '$') {
        continue;
      } elseif ($classParent && $value != null) {
        $this->$field = static::_rehydrateEntity($value, $classParent);
      } elseif ($classChilds && $value != null) {
        $this->$field = static::_rehydrateChilds($value, $classChilds, $map);
      } elseif ($classOthers && $value != null) {
        if (is_array($classOthers)) {
          $this->$field = static::_rehydrateChilds($value, $classOthers, $map);
        } else {
          $this->$field = static::_rehydrateEntity($value, $classOthers);
        }
      } else {
        $this->$field = $value;
      }
    }

    return $this;
  }

  /**
   * @param $deep
   * @return array
   */
  private function _dehydrate($deep) {
    if ($deep) {
      $entity = get_object_vars($this);
      foreach ($entity as &$value) {
        if ($value instanceof self) {
          $value = $value->_dehydrate($deep);
        } elseif (is_object($value)) {
          $value = (array)$value;
        } elseif (is_array($value)) {
          $value = array_map(
            function ($element) use ($deep) {
              if ($element instanceof self) {
                return $element->_dehydrate($deep);
              } elseif (is_object($element)) {
                return (array)$element;
              } else {
                return $element;
              }
            }, $value
          );
        }
      }
    } else {
      $entity = array();
      foreach (get_object_vars($this) as $field => $value) {
        if ($value instanceof self
          || is_array($value)
          || isset(static::$_parent[$field])
          || isset(static::$_childs[$field])
          || isset(static::$_others[$field])
          || isset(static::$_joints[$field])
        ) {
          continue;
        }
        $entity[$field] = $value;
      }
    }

    return $entity;
  }

  /**
   * Locates a child entity by a field and a field value.
   * Depending on the $exists parameter either a boolean is returned determining whether the child was found
   * or the $child entity is returned (or null if not found).
   *
   * @param      $child
   * @param      $field
   * @param      $value
   * @param bool $exists
   * @return AbstractEntity|bool|null
   */
  protected function _filter($child, $field, $value, $exists = false) {
    if (!empty($this->$child)) {
      foreach ($this->$child as &$entity) {
        if ($entity->$field == $value) {
          return $exists ? true : $entity;
        }
      }
    }

    return $exists ? false : null;
  }

  protected function _remove($child, $field, $value, $mark = true) {
    $entity = &$this->_filter($child, $field, $value);
    if (empty($entity)) {
      return null;
    }
    $children = &$this->$child;
    $childID  = $entity->getID();
    if ($mark) {
      $children[$childID] = $childID;
    } else {
      unset($children[$childID]);
    }

    return $entity;
  }

  protected function _find($child, $childID, $exists = false) {
    if (empty($this->$child)) {
      return $exists ? false : null;
    }
    $children = &$this->$child;
    if (empty($children[$childID])) {
      return $exists ? false : null;
    }
    $entity = $children[$childID];

    return $exists ? true : $entity;

  }

  /**
   * Returns TRUE if the $field is an entity property
   *
   * @param $field
   * @return bool
   */
  protected function _isField($field) {
    return property_exists($this, $field);
  }

  /********************************************************************************************************************/

  /**
   * @return string
   */
  public function __toString() {
    return json_encode($this->toArray(), JSON_PRETTY_PRINT);
  }

  /**
   * @return array
   */
  public function toArray() {
    return $this->_dehydrate(true);
  }

  public function getIterator() {
    return new \ArrayIterator($this->toArray());
  }

  /**
   * @param array $data
   * @return $this
   */
  public function merge(array $data) {
    foreach ($data as $field => $newValue) {
      if (!$this->_isField($field)) {
        continue;
      }
      $classParent = static::_parent($field);
      $classChilds = static::_childs($field);
      if ($classParent) {
        $this->$field->merge($newValue);
      } elseif ($classChilds) {
        $this->mergeChildren($field, $newValue);
      } else {
        $this->$field = $newValue;
      }
    }

    return $this;
  }

  public function mergeChildren($field, array $elements) {
    if (!$this->_isField($field)) {
      return $this;
    }
    $childs = &$this->$field;
    $class  = self::_childs($field);
    /** @noinspection PhpUndefinedMethodInspection */
    $model = $class::getModel();
    $pk    = AbstractDB::pk($model);

    $insert = array();
    $update = array();
    $delete = array();
    foreach ($elements as $data) {
      if (!is_array($data)) {
        $id = $data; // ID
        if (!isset($childs[$id])) {
          throw new Exception("Hanging child [{$this::getModel()}][$field][$id]");
        }
        $childs[$id] = $id;
        $delete[]    = $id;
      } elseif (empty($data[$pk])) {
        $new      = self::_build($data, $class);
        $insert[] = $new;
      } else {
        $id = $data[$pk];
        if (!isset($childs[$id])) {
          throw new Exception("Hanging child [{$this::getModel()}][$field][$id]");
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $childs[$id]->merge($data);
        $update[] = $id;
      }
    }
//    foreach ($childs as $id => &$child) {
//      if (!in_array($id, $update)) {
//        $child = $child->getID();
//      }
//    }
    if (count($insert)) {
      $childs += $insert;
    }

    return $this;
  }

  /********************************************************************************************************************/

  /**
   * @var string - timestamp
   */
  public $created;

  /**
   * @var string
   */
  public $_rev;

  /**
   * @var \stdClass
   */
  public $_attachments;

  /********************************************************************************************************************/

  /**
   * @return string
   */
  public function getID() {
    $pk = static::getPK();

    return $this->$pk;
  }

  /**
   * @param string $id
   * @return $this
   */
  public function setID($id) {
    $pk        = static::getPK();
    $this->$pk = $id;

    return $this;
  }

  /**
   * @return bool
   */
  public function hasID() {
    $pk = static::getPK();

    return !empty($this->$pk);
  }


  /**
   * @return string
   */
  public function getRev() {
    return $this->_rev;
  }

  /**
   * @return bool
   */
  public function hasRev() {
    return !empty($this->_rev);
  }

  /**
   * @param string $rev
   * @return $this
   */
  public function setRev($rev) {
    $this->_rev = $rev;

    return $this;
  }

  /********************************************************************************************************************/

  /**
   * @param string|null $name
   * @return \stdClass
   */
  public function getAttachment($name = null) {
    if (empty($this->_attachments)) {
      return null;
    }
    if (empty($name)) {
      return $this->_attachments;
    }
    if (empty($this->_attachments->$name)) {
      return null;
    }

    return $this->_attachments->$name;
  }

  /**
   * @param string|null $name
   * @return bool
   */
  public function hasAttachment($name = null) {
    if (empty($this->_attachments)) {
      return false;
    }
    if (empty($name)) {
      return !empty($this->_attachments);
    }

    return !empty($this->_attachments->$name);
  }

  /**
   * @param string      $name
   * @param bool|false  $absolute
   * @param string|null $default
   * @return object
   */
  public function href($name, $absolute = false, $default = null) {
    return (object)array(
      'model'    => self::getModel(),
      'id'       => $this->getID(),
      'name'     => $name,
      'default'  => $default,
      'absolute' => $absolute,
    );
  }

  /**
   * @param string $name
   * @return null|string
   */
  public function mime($name) {
    $attachment = $this->getAttachment($name);
    if (empty($attachment)) {
      return null;
    }

    return $attachment->content_type;
  }

  /********************************************************************************************************************/

  /**
   * @return $this
   */
  public function tidy() {
    unset($this->_id);
    unset($this->_rev);
    unset($this->_attachments);

    foreach (static::$_parent as $field => $class) {
      if (isset($this->$field)) {
        $this->$field->tidy();
      }
    }

    foreach (static::$_childs as $field => $class) {
      if (isset($this->$field)) {
        $this->$field = array_map(
          function (AbstractEntity $entity) {
            return $entity->tidy();
          }, $this->$field
        );

      } else {
        $this->$field = array();
      }
    }

    foreach (static::$_others as $field => $class) {
      if ($class && isset($this->$field)) {
        $this->$field->tidy();
      }
    }

    return $this;
  }


  /**
   * @return $this
   */
  public function flat() {
    $base = clone $this;

    foreach (static::$_parent as $field => $class) {
      if (isset($base->$field)) {
        $base->$field = $base->$field->flat();
      }
    }

    foreach (static::$_childs as $field => $class) {
      unset($base->$field);
    }

    foreach (static::$_others as $field => $class) {
      if ($class) {
        unset($base->$field);
      }
    }

    return $base->tidy();
  }

  /**
   * @return $this
   */
  public function secure() {
    unset($this->_rev);

    return $this;
  }

  /**
   * @return array
   */
  public function routeParams() {
    return array(
      'id' => $this->getID(),
    );
  }

  /********************************************************************************************************************/

  private function _fieldDifferentEmpty($entity, $field) {
    return empty($this->$field) ^ empty($entity->$field);
  }

  public function equals(AbstractEntity $base) {
    if (empty($base) || (get_class($this) !== get_class($base))) {
      return false;
    }
    if (empty(static::$_equals)) {
      return true;
    }
    foreach (static::$_equals as $field) {
      if (!$this->_isField($field)) {
        continue;
      }
      if (static::_parent($field)) {
        if ($this->_fieldDifferentEmpty($base, $field)) {
          return false;
        }
        if (empty($this->$field)) {
          continue;
        }
        if (!$this->$field->equals($base->$field)) {
          return false;
        }
      } elseif (static::_childs($field)) {
        if ($this->_fieldDifferentEmpty($base, $field)) {
          return false;
        }
        if (empty($this->$field)) {
          continue;
        }
        if (count($this->$field) != count($base->$field)) {
          return false;
        }
        // LATER: compare each child;
      } elseif (static::_joints($field)) {
        if ($this->_fieldDifferentEmpty($base, $field)) {
          return false;
        }
        if (empty($this->$field)) {
          continue;
        }
        if (count($this->$field) != count($base->$field)) {
          return false;
        }
        $diff = array_diff($this->$field, $base->$field);
        if (!empty($diff)) {
          return false;
        }
      } else {
        if ($this->$field != $base->$field) {
          return false;
        };
      }
    }

    return true;
  }

  /********************************************************************************************************************/


  /**
   * @param AbstractEntity $entity
   * @return object
   */
  static public function clearOthers(AbstractEntity $entity) {
    $others = array();
    foreach (static::$_others as $field => $type) {
      $others[$field] = $entity->$field;
      unset($entity->$field);
    }

    return (object)$others;
  }

  /**
   * @param AbstractEntity $entity
   * @param \stdClass      $others
   * @return $this
   */
  static public function resetOthers(AbstractEntity $entity, \stdClass $others) {
    if (!empty($others)) {
      foreach (static::$_others as $field => $type) {
        if (isset($others->$field)) {
          $entity->$field = $others->$field;
        }
      }
    }

    return $entity;
  }

  /********************************************************************************************************************/

}
