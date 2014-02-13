<?php
namespace BeatsBundle\Extension;

use BeatsBundle\Helper\SEO;
use BeatsBundle\Helper\UTC;
use BeatsBundle\OAuth\ResourceProviderMap;
use Twig_Filter_Method;
use Twig_Function_Method;

class TwigExtension extends ContainerAwareTwigExtension {

  public function getTests() {
    return array(
      'traversable' => new \Twig_Test_Method($this, 'isTraversable'),
      'scalar'      => new \Twig_Test_Function('is_scalar')
    );
  }

  public function getFilters() {
    return array(
      'absolute' => new Twig_Filter_Method($this, 'toAbsolute', array('is_safe' => array('html'))),
      'ceil'     => new Twig_Filter_Method($this, 'toCeil', array('is_safe' => array('html'))),
      'options'  => new Twig_Filter_Method($this, 'toOptions', array('is_safe' => array('html'))),
      'slugify'  => new Twig_Filter_Method($this, 'toSlug', array('is_safe' => array('html'))),
      'gmdate'   => new Twig_Filter_Method($this, 'toDate', array('is_safe' => array('html'))),

      'ellipsis' => new Twig_Filter_Method($this, 'ellipsis', array('is_safe' => array('html'))),
    );
  }

  public function getFunctions() {
    return array(
      'hasImage'       => new Twig_Function_Method($this, 'hasImage'),
      'fsalURL'        => new Twig_Function_Method($this, 'fsalURL', array('is_save' => array('html'))),
      'routeCurrent'   => new Twig_Function_Method($this, 'routeCurrent', array('is_safe' => array('html'))),
      'flash'          => new Twig_Function_Method($this, 'renderFlash', array('is_safe' => array('html'))),
      'oauthConnect'   => new Twig_Function_Method($this, 'oauthConnect', array('is_safe' => array('html'))),
      'oauthProviders' => new Twig_Function_Method($this, 'oauthProviders', array('is_safe' => array('html'))),

      'dateY'          => new Twig_Function_Method($this, 'dateY'),
      'dateM'          => new Twig_Function_Method($this, 'dateM'),
      'dateD'          => new Twig_Function_Method($this, 'dateD'),

    );
  }

  public function dateY($upper = null, $lower = null) {
    if (empty($upper)) {
      $upper = date('Y');
    }
    if (empty($lower)) {
      $lower = $upper + 2;
    }
    $opts = array();
    if ($upper < $lower) {
      for ($y = $upper; $y <= $lower; $y++) {
        $y        = sprintf('%04d', $y);
        $opts[$y] = $y;
      }
    } else {
      for ($y = $upper; $lower <= $y; $y--) {
        $y        = sprintf('%04d', $y);
        $opts[$y] = $y;
      }
    }
    return $opts;
  }

  public function dateM($months = true) {
    if (!is_array($months)) {
      $months = $months
        ? ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]
        : ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    }
    $opts = array();
    foreach ($months as $m => $label) {
      $opts[sprintf('%02d', $m + 1)] = $label;
    }
    return $opts;
  }

  public function dateD() {
    $opts = array();
    for ($i = 1; $i < 10; $i++) {
      $i        = '0' . $i;
      $opts[$i] = $i;
    }
    for ($i = 10; $i <= 31; $i++) {
      $i        = '' . $i;
      $opts[$i] = $i;
    }
    return $opts;
  }

  public function isTraversable($value) {
    return empty($value) ? false : is_array($value) || $value instanceof \Traversable;
  }

  public function hasImage($model, $id, $name) {
    return $this->_fsal()->exists($model, $id, $name);
  }

  public function fsalURL($model, $id, $name, $default = null) {
    if (empty($default) || $this->_fsal()->exists($model, $id, $name)) {
      return $this->_fsal()->link($model, $id, $name);
    }
    return $default;
  }

  public function toAbsolute($url) {
    $context = $this->_router()->getContext();
    $port    = '';
    $scheme  = $context->getScheme();
    if ('http' === $scheme && 80 != $context->getHttpPort()) {
      $port = ':' . $context->getHttpPort();
    } elseif ('https' === $scheme && 443 != $context->getHttpsPort()) {
      $port = ':' . $context->getHttpsPort();
    }

    return $scheme . '://' . $context->getHost() . $port . $url;
  }

  public function toCeil($value) {
    return ceil(floatval($value));
  }

  public function toSlug($text) {
    return SEO::slugify($text);
  }

  public function toDate($time, $format, $zone = false) {
    if (empty($zone)) {
      $zone = $this->_chronos()->getTimezone();
    }
    if (empty($zone)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $zone = $this->_twig()->getExtension('core')->getTimezone();
    }
    return UTC::toTZFormat($format, $zone, $time);
  }


  public function ellipsis($value, $length = 160, $suffix = ' ...') {
    $value = trim($value);
    if (empty($value)) {
      return $value;
    }
    $size = mb_strlen($value);
    if ($size <= $length) {
      return $value;
    }
    if (!empty($suffix)) {
      $length -= $size;
    }
    return mb_substr($value, 0, $length) . $suffix;
  }

  public function toOptions($elements, $fVal, $fKey = null, $first = null, $last = null) {
    $options = array();
    if (!empty($elements)) {
      if (empty($fKey)) {
        foreach ($elements as $element) {
          $options[] = $element->$fVal;
        }
      } else {
        foreach ($elements as $element) {
          $options[$element->$fKey] = $element->$fVal;
        }
      }
    }
    if (!empty($first)) {
      $options = $first + $options;
    }
    if (!empty($last)) {
      $options = $options + $last;
    }

    return $options;
  }

  /********************************************************************************************************************/

  public function routeCurrent($value, $active = 'active', $inactive = '') {
    if (strcmp($this->_request()->attributes->get('_route'), $value)) {
      return $inactive;
    } else {
      return $active;
    }
  }

  public function renderFlash() {
    $flashes = array();
    foreach ($this->_flasher()->get() as $flash) {
      $flashes[] = sprintf('<span data-type="%s" data-heading="%s" data-message="%s"></span>',
        $flash->type, htmlentities($flash->heading), htmlentities($flash->message)
      );
    }
    return implode('', $flashes);
  }

  /********************************************************************************************************************/

  /**
   * @return ResourceProviderMap
   */
  final protected function _oauths() {
    return $this->container->get('beats.oauth.resource_provider.map');
  }

  public function oauthConnect($provider) {
    return $this->_oauths()->byName($provider)->urlConnect();
  }

  public function oauthProviders() {
    return $this->_oauths()->getProviders();
  }

}
