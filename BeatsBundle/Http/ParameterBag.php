<?php
namespace BeatsBundle\Http;


class ParameterBag extends \Symfony\Component\HttpFoundation\ParameterBag {

  public function set($key, $value) {
    if (false === $pos = strpos($key, '[')) {
      parent::set($key, $value);
    }
    $root = substr($key, 0, $pos);
    if (preg_match_all('#\[(\w+)\]#', $key, $matches) === false) {
      throw new \RuntimeException("Invalid HTTP parameter key specified: $key");
    }
    $path = $matches[1];

    if (parent::has($root)) {
      $assoc = $this->parameters[$root];
    } else {
      $assoc = array();
    }

    $depth = & $assoc;
    foreach ($path as $name) {
      if (!isset($depth[$name])) {
        $depth[$name] = array();
      }
      $depth = & $depth[$name];
    }
    $depth = $value;

    parent::set($root, $assoc);
  }

  public function flat() {
    $parameters = array();
    foreach ($this->all() as $key => $value) {
      if (is_array($value)) {
        self::_flatten($parameters, $key, [], $value);
      } else {
        $parameters[$key] = $value;
      }
    }
    return $parameters;
  }

  private static function _flatten(&$parameters, $root, $path, $values) {
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        self::_flatten($parameters, $root, array_merge($path, [$key]), $value);
      } else {
        $parameters[$root . '[' . implode('][', $path) . ']'] = $value;
      }
    }
  }

  public function hasDeep($key, $deep = true) {
    if (!$deep || false === $pos = strpos($key, '[')) {
      return array_key_exists($key, $this->parameters);
    }
    $root = substr($key, 0, $pos);
    if (preg_match_all('#\[(\w+)\]#', $key, $matches) === false) {
      throw new \RuntimeException("Invalid HTTP parameter key specified: $key");
    }
    $path = $matches[1];

    if (parent::has($root)) {
      $assoc = $this->parameters[$root];
    } else {
      return false;
    }

    $depth = & $assoc;
    foreach ($path as $name) {
      if (isset($depth[$name]) && is_array($depth[$name])) {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $depth = & $depth[$name];
      }
      return false;
    }
    return true;
  }

}
