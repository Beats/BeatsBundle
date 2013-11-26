<?php
namespace BeatsBundle\Service;

use BeatsBundle\Exception\Exception;
use BeatsBundle\Helper\UTC;
use BeatsBundle\Security\Core\User\Member;
use Symfony\Component\HttpFoundation\Request;

class Chronos extends ContainerAware {

  const SESSION_TIMEZONE = 'beats.chronos.timezone';
  const CONFIG_TIMEZONE  = 'beats.chronos.timezone';
  const COOKIE_TIMEZONE  = 'beats_tz';

  /*********************************************************************************************************************/

  /**
   * @return \Twig_Extension_Core
   */
  final protected function _twigCore() {
    return $this->container->get('twig')->getExtension('core');
  }

  /*********************************************************************************************************************/

  /**
   * @return \DateTimeZone
   */
  public function getDefaultTimezone() {
    static $zone;
    if (empty($zone)) {
      $zone = UTC::createTimeZone($this->container->getParameter(self::CONFIG_TIMEZONE));
    }
    return $zone;
  }

  /**
   * @return \DateTimeZone
   */
  public function getTimezone() {
    $session = $this->_session();
    if ($session->has(self::SESSION_TIMEZONE)) {
      return UTC::createTimeZone($session->get(self::SESSION_TIMEZONE));
    }
    return $this->getDefaultTimezone();
  }

  /**
   * Sets up the client timezone globally
   *
   * @param Request $request
   */
  public function setupTimezone(Request $request) {
    $session  = $this->_session();
    $security = $this->_security();

    if ($session->has(self::SESSION_TIMEZONE)) {
      $zone = $session->get(self::SESSION_TIMEZONE);
    } else {
      if ($security->isGranted('ROLE_USER')) {
        $user = $this->_security()->getToken()->getUser();
        if ($user instanceof Member) {
          $zone = $user->getTimezone();
        }
      }
      if (empty($zone) && $request->cookies->has(self::COOKIE_TIMEZONE)) {
        $zone = $request->cookies->get(self::COOKIE_TIMEZONE);
      }
      if (empty($zone)) {
        $zone = $this->getDefaultTimezone();
      } else {
        $session->set(self::SESSION_TIMEZONE, $zone);
      }
    }
    $this->_twigCore()->setTimezone(UTC::createTimeZone($zone));
  }

  /*********************************************************************************************************************/

  public function now($userID = null) {
    if (empty($userID)) {
      $zone = $this->getTimezone();
    } else {
      throw new Exception("Fetching the timezone for a specific user is not supported at this time");
    }
    return UTC::createDateTime(null, $zone);
  }

}





