<?php
namespace BeatsBundle\Extension;

use BeatsBundle\Exception\Exception;
use BeatsBundle\Helper\SEO;
use BeatsBundle\Helper\UTC;
use BeatsBundle\OAuth\ResourceProviderMap;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig_Filter_Method;
use Twig_Function_Method;

class TwigExtension extends ContainerAwareTwigExtension {

  const CLASS_RDB = 'BeatsBundle\\DBAL\\RDB';

  public function getTests() {
    return array(
      'traversable' => new \Twig_Test_Method($this, 'isTraversable'),
      'scalar'      => new \Twig_Test_Function('is_scalar')
    );
  }

  public function getFilters() {
    return array(
      'fsal'       => new Twig_Filter_Method($this, 'toURL', array('is_safe' => array('html'))),
      'absolute'   => new Twig_Filter_Method($this, 'toAbsolute', array('is_safe' => array('html'))),
      'ceil'       => new Twig_Filter_Method($this, 'toCeil', array('is_safe' => array('html'))),
      'options'    => new Twig_Filter_Method($this, 'toOptions', array('is_safe' => array('html'))),
      'slugify'    => new Twig_Filter_Method($this, 'toSlug', array('is_safe' => array('html'))),

      'gmdate'     => new Twig_Filter_Method($this, 'toDate', array('is_safe' => array('html'))),
      'gmmonth'    => new Twig_Filter_Method($this, 'toMonth', array('is_safe' => array('html'))),
      'gmrelative' => new Twig_Filter_Method($this, 'toRelative', array('is_safe' => array('html'))),

      'ellipsis'   => new Twig_Filter_Method($this, 'ellipsis', array('is_safe' => array('html'))),

      'rdbTable'   => new \Twig_SimpleFilter(
        'rdbTable', array(self::CLASS_RDB, 'table'), array('is_safe' => array('sql'))
      ),
      'rdbPK'      => new \Twig_SimpleFilter(
        'rdbPK', array(self::CLASS_RDB, 'pk'), array('is_safe' => array('sql'))
      ),
    );
  }

  public function getFunctions() {
    return array(
      'hasImage'       => new Twig_Function_Method($this, 'hasImage'),
      'fsalURL'        => new Twig_Function_Method($this, 'fsalURL', array('is_save' => array('html'))),
      'routeCurrent'   => new Twig_Function_Method($this, 'routeCurrent', array('is_safe' => array('html'))),
      'currentPath'    => new Twig_Function_Method($this, 'currentPath', array('is_safe' => array('html'))),
      'authPath'       => new Twig_Function_Method($this, 'authPath', array('is_safe' => array('html'))),
      'currentRoute'   => new Twig_Function_Method($this, 'currentRoute', array('is_safe' => array('html'))),
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

  public function toURL($params, $default = null, $absolute = false) {
    $params = (object)$params;

    return $this->fsalURL(
      $params->model,
      $params->id,
      $params->name,
      empty($params->default) ? $default : $params->default,
      empty($params->absolute) ? $absolute : $params->absolute
    );
  }

  public function fsalURL($model, $id, $name, $default = null, $absolute = false) {
    if (empty($id)) {
      return $default;
    }
    if (empty($default)  || $this->_fsal()->exists($model, $id, $name)) {
      return $this->_fsal()->link($model, $id, $name, $absolute);
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

  /********************************************************************************************************************/

  public function toDate($time, $format = 'Y-m-d H:i:s', $zone = false) {
    if (empty($zone)) {
      $zone = $this->_chronos()->getTimezone();
    }
    if (empty($zone)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $zone = $this->_twig()->getExtension('core')->getTimezone();
    }

    return UTC::toTZFormat($format, $zone, $time);
  }

  public function toMonth($month, $format, $zone = false) {
    if (empty($zone)) {
      $zone = $this->_chronos()->getTimezone();
    }
    if (empty($zone)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $zone = $this->_twig()->getExtension('core')->getTimezone();
    }

    if (preg_match('#^((?<y>\d{4})-)?(?<m>\d{1,2})#', $month, $matches)) {
      $time = implode('-', array(empty($matches['y']) ? '2000' : $matches['y'], $matches['m'], '01'));
    } else {
      throw new Exception("Invalid month format: $month");
    }

    return UTC::toTZFormat($format, $zone, $time);
  }

  public function toRelative($time) {
    $ts     = UTC::toUNIX($time);
    $diff_s = time() - $ts;

    // TODO@ion: refactor and translate this
    if (0 < $diff_s) {
      $diff_d = floor($diff_s / 86400);
      if ($diff_d == 0) {
        if ($diff_s < 60) return 'just now';
        if ($diff_s < 120) return '1 minute ago';
        if ($diff_s < 3600) return floor($diff_s / 60) . ' minutes ago';
        if ($diff_s < 7200) return '1 hour ago';
        if ($diff_s < 86400) return floor($diff_s / 3600) . ' hours ago';
      }
      if ($diff_d == 1) return 'Yesterday';
      if ($diff_d < 7) return $diff_d . ' days ago';
      if ($diff_d < 31) return ceil($diff_d / 7) . ' weeks ago';
      if ($diff_d < 60) return 'last month';

      return date('F Y', $ts);

    } elseif ($diff_s < 0) {

      $diff_s = abs($diff_s);
      $diff_d = floor($diff_s / 86400);
      if ($diff_d == 0) {
        if ($diff_s < 120) return 'in a minute';
        if ($diff_s < 3600) return 'in ' . floor($diff_s / 60) . ' minutes';
        if ($diff_s < 7200) return 'in an hour';
        if ($diff_s < 86400) return 'in ' . floor($diff_s / 3600) . ' hours';
      }
      if ($diff_d == 1) return 'Tomorrow';
      if ($diff_d < 4) return date('l', $ts);
      if ($diff_d < 7 + (7 - date('w'))) return 'next week';
      if (ceil($diff_d / 7) < 4) return 'in ' . ceil($diff_d / 7) . ' weeks';
      if (date('n', $ts) == date('n') + 1) return 'next month';

      return date('F Y', $ts);
    } else {
      return 'now';
    }
  }

  /********************************************************************************************************************/

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

  public function currentRoute($suffix = '') {
    $route = $this->_request()->attributes->get('_route');

    return empty($suffix) ? $route : implode('.', array($route, $suffix));
  }

  public function routeCurrent($value, $active = 'active', $inactive = '') {
    if (strcmp($this->currentRoute(), $value)) {
      return $inactive;
    } else {
      return $active;
    }
  }

  public function renderFlash() {
    $flashes = array();
    foreach ($this->_flasher()->get() as $flash) {
      $flashes[] = sprintf(
        '<span data-type="%s" data-heading="%s" data-message="%s"></span>',
        $flash->type, htmlentities($flash->heading), htmlentities($flash->message)
      );
    }

    return implode('', $flashes);
  }

  public function currentPath($relative = false) {
    try {
      return $this->_router()->generate(
        $this->_request()->attributes->get('_route'), $this->_request()->attributes->get('_route_params'),
        $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH
      );
    } catch (\Exception $ex) {
      $this->_logger()->error($ex->getMessage());

      return null;
    }
  }

  public function authPath($loginRoute, array $params = array(), $relative = false) {
    $targetPath = $this->currentPath($relative);
    if (!empty($targetPath)) {
      $this->_session()->set('_target_path', $targetPath);
    }

    return $this->_router()->generate($loginRoute, $params, $relative);
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
