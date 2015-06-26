<?php
namespace BeatsBundle\Model;


use BeatsBundle\Exception\Exception;
use BeatsBundle\Security\Core\User\Member;
use BeatsBundle\Security\User\UserInterface;
use BeatsBundle\Service\Aware\BrowserAware;
use BeatsBundle\Service\Aware\ClientDeviceAware;
use BeatsBundle\Service\Aware\DBALAware;
use BeatsBundle\Service\Aware\FlasherAware;
use BeatsBundle\Service\Aware\FSALAware;
use BeatsBundle\Service\Aware\ValidatorAware;
use BeatsBundle\Service\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The inherited classes of this class are holders of the project business logic.
 *
 * This class provides the necessary tools such as DBAL extends and other services
 *
 * ALL THE BUSINESS LOGIC GOES HERE
 */
abstract class AbstractModel extends ContainerAware {
  use FSALAware, DBALAware, FlasherAware, ValidatorAware, BrowserAware, ClientDeviceAware;

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

  /**
   * Returns an AccessDenied Exception with the provided message.
   * If the message is a boolean it will render the default message
   * for Anonymous users (TRUE) or Forbidden (FALSE)
   *
   * @param string|bool $message The exception message
   * @param \Exception  $previous
   * @param bool        $logout  Whether to forcibly logout the user
   * @return AccessDeniedHttpException
   */
  protected function _createAccessDeniedException($message = false, \Exception $previous = null, $logout = false) {
    if (is_bool($message)) {
      if ($message) {
        $message = $this->_trans("beats.exception.access_denied.anonymous.ok"); // Anonymous
      } else {
        $message = $this->_trans("beats.exception.access_denied.anonymous.no"); // Forbidden
      }
    }
    if ($logout) {
      $this->_security()->setToken(null);
    }

    return new AccessDeniedHttpException($message, $previous);
  }

  /**
   * Returns a NotFound Exception with the provided message
   * If the message is empty a default message is used
   *
   * @param string|null $message
   * @param \Exception  $previous
   * @return NotFoundHttpException
   */
  protected function _createNotFoundException($message = null, \Exception $previous = null) {
    if (empty($message)) {
      $message = $this->_trans('beats.exception.not_found');
    }

    return new NotFoundHttpException($message, $previous);
  }

  /*********************************************************************************************************************/

  /**
   * @param bool $throw
   *
   * @return Member|null
   */
  public function getMember($throw = true) {
    $token = $this->_security()->getToken();
    if (empty($token) || !$token->isAuthenticated()) {
      if ($throw) {
        throw $this->_createAccessDeniedException(true);
      }

      return null;
    }
    $user = $token->getUser();
    if (is_string($user)) {
      if ($throw) {
        throw $this->_createAccessDeniedException(true);
      }

      return null;
    }

    return $token->getUser();
  }

  /**
   * @param bool $throw
   *
   * @return string|null
   */
  public function getMemberID($throw = false) {
    $member = $this->getMember($throw);
    if (empty($member)) {
      return null;
    }

    return $member->getID();
  }

  /**
   * @param bool $throw
   *
   * @return UserInterface|null
   */
  public function getMemberUser($throw = false) {
    $member = $this->getMember($throw);
    if (empty($member)) {
      return null;
    }

    return $member->getUser();
  }

  /**
   * @param UserInterface $user
   * @return UserInterface
   */
  protected function _invalidateMember(UserInterface $user) {
    if ($user->getID() == $this->getMemberID(false)) {
      $this->getMember()->invalidate();
    }

    return $user;
  }

  /**
   * @return bool
   */
  public function isAnonymous() {
    $member = $this->getMember();

    return empty($member);
  }

  /**
   * @return bool
   */
  public function isAuthenticated() {
    return !$this->isAnonymous();
  }

  /*********************************************************************************************************************/

  /**
   * @deprecated
   * @return array
   */
  public function getGlobals() {
    return $this->container->get('twig')->getGlobals();
  }

  /**
   * @deprecated To be replaced with a PRNG service
   *
   * @param int $length
   *
   * @return string
   */
  protected function _randomPassword($length = 8) {
    static $alphabet = array(
      '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '_',
      'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
      'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
      'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
      'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    );
    shuffle($alphabet);

    return implode('', array_slice($alphabet, 0, $length));
  }

  /*********************************************************************************************************************/

}
