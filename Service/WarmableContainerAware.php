<?php
namespace BeatsBundle\Service;

use BeatsBundle\Service\ContainerAware;
use BeatsBundle\CacheWarmer\Persister;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

abstract class WarmableContainerAware extends ContainerAware implements CacheWarmerInterface {

  /*********************************************************************************************************************/

  /**
   * @return Persister
   */
  final protected function _cachePersister() {
    return $this->container->get('beats.cache_warmer.persister');
  }

  /*********************************************************************************************************************/

}
