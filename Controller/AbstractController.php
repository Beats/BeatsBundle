<?php
namespace BeatsBundle\Controller;

use BeatsBundle\Model\ModelException;
use BeatsBundle\Security\Core\User\Member;
use BeatsBundle\Service\Aware\ClientDeviceAware;
use BeatsBundle\Service\Aware\FlasherAware;
use BeatsBundle\Service\Aware\ValidatorAware;
use BeatsBundle\Service\Service;
use BeatsBundle\Session\Message;
use BeatsBundle\Session\Page;
use BeatsBundle\Validation\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AbstractController extends Controller {
  use Service, FlasherAware, ValidatorAware, ClientDeviceAware;

  const REGEX_TEMPLATE = '#(?<bundle>.+)\\\\Controller\\\\(?<type>\w+)\\\\(?<class>\w+)Controller::(?<action>\w+)Action#';

  /*********************************************************************************************************************/

  /**
   * Returns the default template by type for the current action
   *
   * @param string $type
   *
   * @return string
   */
  protected function _template($type = 'html') {
    $request = $this->_request();
    $request->setRequestFormat($type);
    if (preg_match(self::REGEX_TEMPLATE, $request->attributes->get('_controller'), $part)) {
      return implode(
        ':',
        array(
          str_replace('\\', '', $part['bundle']),
          $part['class'],
          implode(
            '.',
            array(
              $part['action'],
              strtolower($part['type']),
              'twig',
            )
          ),
        )
      );
    };

    return preg_replace(
      array(
        '#\\\\Controller\\\\#', '#\\\\#', '#Controller::#', '#Action$#',
      ),
      array(
        ':', '', ':', '.' . $type . '.twig',
      ),
      $request->attributes->get('_controller')
    );
  }

  protected function _currentPath($relative = false) {
    return $this->_router()->generate(
      $this->_request()->attributes->get('_route'), $this->_request()->attributes->get('_route_params'),
      $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH
    );
  }

  /********************************************************************************************************************/

  /**
   * Renders the default template by type for the current action
   *
   * @param array    $parameters
   * @param string   $type
   * @param Response $response
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function renderAction(array $parameters = array(), $type = 'html', Response $response = null) {
    return $this->render($this->_template($type), $parameters, $response);
  }

  static public function ajaxMessage($success = true, $message = 'Success', $data = null) {
    if ($success instanceof \Exception) {
      $message = $success->getMessage();
      $success = false;
    } else {
      if (empty($message)) {
        $message = $success ? 'Success' : 'Failure';
      }
    }
    $content = array(
      'success' => $success,
      'message' => $message,
      'data'    => $data,
    );

    return $content;
  }

  public function renderAJAX($success, $message = null, $data = null, Response $response = null) {
    return $this->renderJSON(self::ajaxMessage($success, $message, $data), $response);
  }

  public function renderJSON($json, Response $response = null) {
    $content = json_encode($json, JSON_PRETTY_PRINT);
    if (empty($response)) {
      $response = new Response($content);
    } else {
      $response->setContent($content);
    }
    $response->headers->set('Content-Type', 'application/json');

    return $response;
  }

  public function render($view, array $parameters = array(), Response $response = null) {
    $parameters = array_merge(
      $parameters,
      array(
        '_fields' => $this->_validator()->getMessages(),
      )
    );

    return parent::render($view, $parameters, $response);
  }

  /********************************************************************************************************************/

  /**
   * @param       $route
   * @param array $parameter
   * @param bool  $absolute
   * @param int   $status
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function redirectTo($route, array $parameter = array(), $absolute = false, $status = 302) {
    return $this->redirect($this->generateUrl($route, $parameter, $absolute), $status);
  }

  /**
   * @param Message         $message
   * @param null|bool|array $routeName
   * @param array           $routeArgs
   * @param bool            $absolute
   * @param int             $status
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function redirectFlashTo(
    Message $message, $routeName = null, array $routeArgs = array(), $absolute = true, $status = 302
  ) {
    $this->_flasher()->add($message);
    if (empty($routeName) || $message instanceof Page) {
      return $this->redirectTo('beats.basic.html.flash', $routeArgs, $absolute, $status);
    } elseif (is_bool($routeName)) {
      return $this->redirect($this->_flasher()->getCurrentURL($routeArgs), $status);
    }

    return $this->redirectTo($routeName, $routeArgs);
  }

  /**
   * @param Message     $message
   * @param null|string $url
   * @param array       $parameters
   * @param int         $status
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function redirectFlash(Message $message, $url = null, array $parameters = array(), $status = 302) {
    $this->_flasher()->add($message);
    if (empty($url) || $message instanceof Page) {
      return $this->redirectTo('beats.basic.html.flash', $parameters, false, $status);
    } elseif (is_bool($url)) {
      return $this->redirect($this->_flasher()->getCurrentURL($parameters), $status);
    } else {
      return $this->redirect($url, $status);
    }
  }

  public function redirectError(\Exception $ex, array $parameters = array(), $absolute = true, $status = 302) {
    if ($ex instanceof ModelException) {
      if ($ex->hasFlash()) {
        $message    = $ex->flash;
        $route      = $ex->routeName;
        $parameters = array_merge($parameters, $ex->routeArgs);
      } else {
        $message = Message::failure($ex->getMessage());
        $route   = true;
      }
    } else {
      $message = Page::failure($ex->getMessage());
      $route   = true;
    }

    return $this->redirectFlashTo($message, $route, $parameters, $absolute, $status);
  }

  /********************************************************************************************************************/

  /**
   * @return Member
   */
  public function getMember() {
    return parent::getUser();
  }

  /**
   * @return \BeatsBundle\Security\User\UserInterface|null
   */
  public function getMemberUser() {
    $member = $this->getMember();
    if (empty($member)) {
      return null;
    }

    return $member->getUser();
  }

  public function isAnonymous() {
    $member = $this->getMember();

    return empty($member);
  }

  public function isAuthenticated() {
    return !$this->isAnonymous();
  }

  /********************************************************************************************************************/


  /**
   * Returns an AccessDenied Exception with the given message
   * If the message is a boolean it will render the default message for Anonymous users (TRUE) or Forbidden (FALSE)
   *
   * @param string|bool $message The exception message
   * @param \Exception  $previous
   * @param bool        $logout  Whether to forcibly logout the user
   *
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
   *
   * @return NotFoundHttpException
   */
  protected function _createNotFoundException($message = null, \Exception $previous = null) {
    if (empty($message)) {
      $message = $this->_trans('beats.exception.not_found');
    }

    return new NotFoundHttpException($message, $previous);
  }

  /**
   * Returns a BadRequest Exception with the given message
   *
   * @param string|null     $message
   * @param \Exception|null $previous
   *
   * @return BadRequestHttpException
   */
  protected function _createBadRequestException($message = null, \Exception $previous = null) {
    if (empty($message)) {
      $message = $this->_trans('beats.exception.bad_request');
    }

    return new BadRequestHttpException($message, $previous);
  }

  /**
   * Returns a ServerError Exception with the given message
   *
   * @param string|null     $message
   * @param \Exception|null $previous
   *
   * @return HttpException
   */
  protected function _createServerErrorException($message = null, \Exception $previous = null) {
    if (empty($message)) {
      $message = $this->_trans('beats.exception.server_error');
    }

    return new HttpException(500, $message, $previous);
  }

  /********************************************************************************************************************/

  protected function _api(\Closure $callback) {
    try {
      $args    = func_get_args();
      $args[0] = $this->_request();
      $data    = call_user_func_array($callback, $args);

      return $this->renderAJAX(true, null, $data);
    } catch (ValidationException $ex) {
      return $this->renderAJAX(false, $ex->getMessage(), $ex->getResponse());
    } catch (ModelException $ex) {
      return $this->renderAJAX(false, $ex->getMessage());
    } catch (\Exception $ex) {
      return $this->renderAJAX($ex);
    }
  }

  /********************************************************************************************************************/

}
