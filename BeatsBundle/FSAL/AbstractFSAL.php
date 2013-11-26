<?php
namespace BeatsBundle\FSAL;

use BeatsBundle\Exception\FSALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Kernel;

abstract class AbstractFSAL extends ContainerAware {


  public static function ensureDir($directory) {
    if (!is_dir($directory)) {
      if (false === @mkdir($directory, 0777, true)) {
        throw new FSALException(sprintf('Unable to create the "%s" directory', $directory));
      }
    } elseif (!is_writable($directory)) {
      throw new FSALException(sprintf('Unable to write in the "%s" directory', $directory));
    }
    return $directory;
  }

  /**
   * @return Kernel
   */
  final protected function _kernel() {
    return $this->container->get('kernel');
  }

  /**
   * @return LoggerInterface
   */
  final protected function _logger() {
    return $this->container->get('logger');
  }

  public function __construct(ContainerInterface $container) {
    $this->setContainer($container);
  }

  public function logException(\Exception $ex) {
    return $this->_logger()->error($ex->getMessage(), $ex->getTrace());
  }

  /**
   * @return Filesystem
   */
  public function fs() {
    return $this->container->get('filesystem');
  }

  abstract public function attach($model, $id, $name, File $file, $erase = true);

  abstract public function attachAll($model, $id, $prefix, $files, $erase = true);

  abstract public function detach($model, $id, $name);

  abstract public function detachAll($model, $id, $prefix);

  /**
   * @param string $model Model name
   * @param string $id Model document id
   * @param string $name Attachment name
   * @param string|null $path The directory where to place the loaded attachment
   * @return File|bool
   */
  abstract public function file($model, $id, $name, $path = null);

  abstract public function link($model, $id, $name, $absolute = false);

  abstract public function exists($model, $id, $name);

  public function temporary($extension = null, $prefix = null, $home = null) {
    $suffix = empty($extension) ? '' : '.' . $extension;
    $home   = empty($home) ? array($this->_kernel()->getRootDir(), 'Resources', 'data') : $home;
//    $home = empty($home) ? sys_get_temp_dir() : $home ;
    $temp = tempnam(implode(DIRECTORY_SEPARATOR, $home), $prefix);
    $file = $temp . $suffix;
    rename($temp, $file);
    return $file;
  }

  /**
   * @param $url
   * @param null $extension
   * @param null $prefix
   * @param null $home
   * @return File
   */
  public function download($url, $extension = null, $prefix = null, $home = null) {
    $tmp = $this->temporary($extension, $prefix, $home);
    file_put_contents($tmp, fopen($url, 'r'));
    return new File($tmp);
  }

  public function erase(File $file) {
    return unlink($file->getRealPath());
  }

  public function eraseAll(array $files) {
    if (empty($files)) {
      return;
    }
    foreach ($files as $file) {
      try {
        $this->erase($file);
      } catch (\Exception $ex) {
        $this->logException($ex);
      }
    }
  }

  public function store(UploadedFile $upload, $name = null, $home = null) {
    $home = empty($home) ? sys_get_temp_dir() : self::ensureDir($home);
    $name = empty($name) ? basename(tempnam($home, 'beats_upload_')) : $name;
    return $upload->move($home, $name)->getRealPath();
  }

  static protected function stream(File $file) {
    $f = fopen($file->getRealPath(), 'r');
    $o = fopen('php://output', 'wb');
    stream_copy_to_stream($f, $o);
    fclose($f);
    fclose($o);
  }

  public function respond($path, Response $response = null) {
    if (empty($path)) {
      return false;
    }
    $file = new File($path);
    if (!$file->isReadable()) {
      return false;
    }

    $response = new StreamedResponse(function () use ($file) {
      $f = fopen($file->getRealPath(), 'r');
      $o = fopen('php://output', 'wb');
      stream_copy_to_stream($f, $o);
      fclose($f);
      fclose($o);
    });
    $response->headers->set('Content-Length', $file->getSize());
    $response->headers->set('Accept-Ranges', 'bytes');
    $response->headers->set('Content-Transfer-Encoding', 'binary');
    $response->headers->set('Content-Type', $file->getMimeType() ? : 'application/octet-stream');

    return $response;
  }

}