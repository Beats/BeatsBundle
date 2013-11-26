<?php
namespace BeatsBundle\Routing;


use BeatsBundle\Service\WarmableContainerAware;
use Symfony\Component\Routing\Route;

class Exporter extends WarmableContainerAware {

  const SCRIPT_VARS = 'routes';
  const SCRIPT_FILE = 'routes.js';
  const CACHED_FILE = 'routes.php.meta';

  /**
   * @var array
   */
  private $_routes;

  /**
   * @return array
   */
  public function getRoutes() {
    if (empty($this->_routes)) {
      $this->_routes = $this->_build();
    }
    return $this->_routes;
  }

  /*********************************************************************************************************************/

  /**
   * Warms up the cache.
   *
   * @param string $cacheDir The cache directory
   * @return array
   */
  public function warmUp($cacheDir) {
    $routes = $this->getRoutes();

    $this->_cachePersister()->store($routes, self::CACHED_FILE, $cacheDir);

    $this->_export($routes);

    return $routes;
  }

  public function isOptional() {
    return false;
  }

  /*********************************************************************************************************************/

  protected function _build() {
    $router = $this->_router();

//    $host = $router->getContext()->getHost();
    $host = null;

    $routes = array();
    foreach ($router->getRouteCollection()->all() as $name => $route) {
      if ($name[0] == '_') {
        continue;
      }
      $routes[$name] = $this->_parse($route, $host, $name);
    }
    return $routes;
  }

  protected function _parse(Route $route, $host, $name) {
    $oldHost = $route->getHost();
    $route->setHost($host);
    $compiled = $route->compile();
    $route->setHost($oldHost);
    $defaults = $route->getDefaults();
    unset($defaults['_controller']);
    return array(
      'methods'    => $route->getMethods(),
      'schemes'    => $route->getSchemes(),
      'hostname'   => $host,
      'defaults'   => $defaults,
      'controller' => $route->getDefault('_controller'),
      'format'     => $route->getRequirement('_format'),

      'required'   => $route->getRequirements(),
      'path'       => array(
        'vars'   => $compiled->getPathVariables(),
        'tokens' => $compiled->getTokens(),
      ),
      'host'       => array(
        'vars'   => $compiled->getHostVariables(),
        'tokens' => $compiled->getHostTokens(),
      ),
    );
  }

  protected function _export($routes, $file = self::SCRIPT_FILE, $vars = self::SCRIPT_VARS) {
    $path = implode(DIRECTORY_SEPARATOR, array(
      dirname($this->_kernel()->getRootDir()), 'web', $file,
    ));

    $content = sprintf('var %s = %s', $vars, json_encode($routes, JSON_PRETTY_PRINT));
    if (file_put_contents($path, $content) === false) {
      throw new \RuntimeException("Could not create a routing definition file: $path");
    }
  }


  /*********************************************************************************************************************/

}





