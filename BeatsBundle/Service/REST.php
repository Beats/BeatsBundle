<?php
namespace BeatsBundle\Service;

use Buzz\Browser;
use Buzz\Exception\ClientException;
use Buzz\Listener\ListenerChain;
use Buzz\Listener\ListenerInterface;
use Buzz\Message;
use Buzz\Util\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class REST extends ContainerAware {

  /********************************************************************************************************************/

  /**
   * @return Browser
   */
  final  protected function _httpClient() {
    return $this->container->get('buzz.browser');
  }

  /********************************************************************************************************************/

  public function __construct(ContainerInterface $container, array $options = array(), ListenerInterface $listener = null) {
    parent::__construct($container, array_merge(array(
      'rest_host' => null,
      'timeout'   => 30,
    ), $options));
    if (empty($listener)) {
      $listener = new ListenerChain();
    }

    $this->_httpClient()->setListener($listener);
  }

  public function getHost() {
    return $this->_options->get('rest_host');
  }

  public function getTimeout() {
    return $this->_options->get('timeout');
  }

  /********************************************************************************************************************/

  protected function _setupHeaders(Message\Request $request, array $headers = array()) {
    $request->setHeaders(array_merge(array(
      'User-Agent' => self::USER_AGENT,
    ), $headers));
    return $this;
  }

  protected function _setupContent(Message\Request $request, $content) {
    if (!empty($content)) {
      if ($request instanceof Message\Form\FormRequest) {
        $request->addFields($content);
      } else {
        $request->setContent($content);
      }
    }
    return $this;
  }

  /**
   * @param Message\Response $response
   * @return string
   */
  protected function _parseResponse(Message\Response $response) {
    return $response->getContent();
  }

  /**
   *
   * Performs an HTTP request
   *
   * @param string $url The url to fetch
   * @param string $content The content of the request
   * @param array $headers The headers of the request
   * @param string $method The HTTP method to use
   *
   * @return mixed The response content
   */
  protected function _send($method, $url, $content = null, array $headers = array()) {
    if (!$url instanceof Url) {
      $url = new Url($url);
    }
    $httpClient = $this->_httpClient();

    $request = $httpClient->getMessageFactory()->createFormRequest($method);
    $url->applyToRequest($request);
    if (!$request->getHost()) {
      $request->setHost($this->getHost());
    }
    $this->_setupHeaders($request, $headers);
    $this->_setupContent($request, $content);

    $httpClient->getClient()->setTimeout($this->getTimeout());

    $response = $httpClient->getMessageFactory()->createResponse();
    try {
      return $this->_parseResponse($httpClient->send($request, $response));
    } catch (ClientException $ex) {
      return $this->_parseResponse($response, $ex);
    }
  }

  /*********************************************************************************************************************/

  public function find($uri, $content = null) {
    return $this->_send(Message\Request::METHOD_GET, $uri, $content);
  }

  public function make($uri, $content = null) {
    return $this->_send(Message\Request::METHOD_POST, $uri, $content);
  }

  public function edit($uri, $content = null) {
    return $this->_send(Message\Request::METHOD_PUT, $uri, $content);
  }

  public function kill($uri, $content = null) {
    return $this->_send(Message\Request::METHOD_DELETE, $uri, $content);
  }

  /*********************************************************************************************************************/

}





