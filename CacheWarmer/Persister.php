<?php
namespace BeatsBundle\CacheWarmer;

use BeatsBundle\Service\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class Persister extends ContainerAware {

  const DEF_CACHE_DIR_NAME = 'beats';

  const OPT_CACHE_DIR_PATH = 'beats.cache.dir.path';
  const OPT_CACHE_DIR_NAME = 'beats.cache.dir.name';

  /**
   * @return Filesystem
   */
  final protected function _fs() {
    return $this->container->get('filesystem');
  }

  /********************************************************************************************************************/

  private $_home;

  public function __construct(ContainerInterface $container, array $options = array()) {
    parent::__construct($container, $options);
  }

  /********************************************************************************************************************/

  public function getHome($cacheDir = null) {
    if (empty($this->_home)) {
      if ($this->_options->has(self::OPT_CACHE_DIR_PATH)) {
        $this->_home = $this->_options->get(self::OPT_CACHE_DIR_PATH);
      } else {
        $this->_home = implode(DIRECTORY_SEPARATOR, array(
          empty($cacheDir)
            ? $this->_kernel()->getCacheDir()
            : $cacheDir,
          $this->_options->has(self::OPT_CACHE_DIR_NAME)
            ? $this->_options->get(self::OPT_CACHE_DIR_NAME)
            : self::DEF_CACHE_DIR_NAME,
        ));
      }
      $this->_fs()->mkdir($this->_home);
    }
    return $this->_home;
  }

  protected function _path($path, $cacheDir = null) {
    $path = implode(DIRECTORY_SEPARATOR, array(
      $this->getHome($cacheDir),
      ltrim($path, DIRECTORY_SEPARATOR),
    ));
    $this->_fs()->mkdir(dirname($path));
    return $path;
  }

  /********************************************************************************************************************/

  /**
   * @param $content
   * @param $name
   * @param null $cacheDir
   *
   * @return mixed
   * @throws \RuntimeException
   */
  public function store($content, $name, $cacheDir = null) {
    $this->_save($name, $this->_export($content), $cacheDir);
    return $content;
  }

  /**
   * @param $name
   * @param null $cacheDir
   *
   * @return mixed
   * @throws \RuntimeException
   */
  public function load($name, $cacheDir = null) {
    return $this->_import($this->_read($name, $cacheDir));
  }

  /********************************************************************************************************************/

  protected function _read($name, $cacheDir = null) {
    $file = $this->_path($name, $cacheDir);
    if (is_readable($file)) {
      return file_get_contents($file);
    }
    throw new \RuntimeException("Cache file not readable: $file");
  }

  protected function _save($name, $encoded, $cacheDir = null) {
    $file = $this->_path($name, $cacheDir);
    $temp = tempnam(dirname($file), basename($file));
    if (false === @file_put_contents($temp, $encoded) || !@rename($temp, $file)) {
      throw new \RuntimeException("Cache file not writable: $file");
    }
    @chmod($file, 0666 & ~umask());
    return $this;
  }

  /********************************************************************************************************************/

  /**
   * @param $content
   * @return string
   */
  protected function _export($content) {
    return serialize($content);
  }

  /**
   * @param string $encoded
   * @return mixed
   */
  protected function _import($encoded) {
    return unserialize($encoded);
  }

  /********************************************************************************************************************/

}
