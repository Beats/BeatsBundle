<?php
namespace BeatsBundle\Model;


use BeatsBundle\Exception\Exception;
use BeatsBundle\Service\Aware\BrowserAware;
use BeatsBundle\Service\Aware\DBALAware;
use BeatsBundle\Service\Aware\FlasherAware;
use BeatsBundle\Service\Aware\FSALAware;
use BeatsBundle\Service\Aware\ValidatorAware;
use BeatsBundle\Service\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The inherited classes of this class are holders of the project business logic.
 *
 * This class provides the necessary tools such as DBAL extends and other services
 *
 * ALL THE BUSINESS LOGIC GOES HERE
 */
abstract class AbstractModel extends ContainerAware {
  use FSALAware, DBALAware, FlasherAware, ValidatorAware, BrowserAware;

  /*********************************************************************************************************************/

  static protected function _extend(array $old, array $new) {
    foreach ($new as $key => $val) {
      if (is_array($val)) {
        if (isset($old[$key])) {
          $old[$key] = self::_extend($old[$key], $val);
        } else {
          $old[$key] = $val;
        }
      } else {
        $old[$key] = $val;
      }
    }

    return $old;
  }

  /**
   * To be used for Model initialization
   */
  protected function _init() {
  }

  /*********************************************************************************************************************/

  public function __construct(ContainerInterface $container) {
    $this->setContainer($container);
    $this->_init();
  }

  /*********************************************************************************************************************/

  public function logException(\Exception $ex) {
    return $this->_logger()->error($ex->getMessage(), $ex->getTrace());
  }

  /*********************************************************************************************************************/

  protected function _getValidationSet(array $validations, $type) {
    if (empty($type)) {
      $type = false;
    }
    if (is_bool($type)) {
      $type = $type ? 'update' : 'insert';
    }
    if (array_key_exists($type, $validations)) {
      return $validations[$type];
    }
    throw new Exception("Unknown validation set: $type");

  }
  /*********************************************************************************************************************/

}
