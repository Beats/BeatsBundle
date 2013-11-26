<?php
namespace BeatsBundle\Newsletter;

use BeatsBundle\Service\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Dummy extends ContainerAware implements NewsletterInterface {

  /********************************************************************************************************************/

  public function __construct(ContainerInterface $container, array $options = array()) {
    parent::__construct($container, $options);
  }

  /********************************************************************************************************************/

  /**
   * @return string
   */
  private function _getKey() {
    return $this->_options->get('key');
  }

  /**
   * @return array
   */
  private function _getIDs() {
    return $this->_options->get('ids');
  }

  /********************************************************************************************************************/

  public function subscribe($newsletter, $email) {
    return true;
  }

  /**
   * @param $newsletter
   * @param $email
   * @return bool
   */
  public function unsubscribe($newsletter, $email) {
    return true;
  }

  /********************************************************************************************************************/

}