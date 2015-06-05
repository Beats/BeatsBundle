<?php
namespace BeatsBundle\Service\Aware;

use Detection\MobileDetect;
use SunCat\MobileDetectBundle\Helper\DeviceView;

trait ClientDeviceAware {

  /**
   * @return MobileDetect
   */
  final protected function _deviceDetector() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('mobile_detect.mobile_detector');
  }

  /**
   * @return DeviceView
   */
  final protected function _device() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->container->get('mobile_detect.device_view');
  }

}