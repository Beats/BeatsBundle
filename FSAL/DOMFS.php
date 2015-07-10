<?php
namespace BeatsBundle\FSAL;

use BeatsBundle\DBAL\DOM;
use BeatsBundle\Exception\Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\Routing\RouterInterface;

/**
 * Implement cURL REST CouchDB proxy ...
 */
class DOMFS extends AbstractFSAL {

  /**
   * @return DOM
   */
  protected function _dom() {
    return $this->container->get('beats.dbal.dom');
  }

  /**
   * @return RouterInterface
   */
  protected function _router() {
    return $this->container->get('router');
  }

  public static function ensureDir($directory) {
    if (!is_dir($directory)) {
      if (false === @mkdir($directory, 0777, true)) {
        throw new FileException(sprintf('Unable to create the "%s" directory', $directory));
      }
    } elseif (!is_writable($directory)) {
      throw new FileException(sprintf('Unable to write in the "%s" directory', $directory));
    }

    return $directory;
  }

  public function attach($model, $id, $name, File $file, $erase = true) {
    $response = $this->_dom()->store($model, $id, $file, $name);
    if ($response->ok && $erase) {
      $this->erase($file);
    }

    return $response->ok;
  }

  public function attachAll($model, $id, $prefix, $files, $erase = true) {
    if (!empty($files)) {
      $rev = null;
      foreach ($files as $type => $file) {
        try {
          $response = $this->_dom()->store($model, $id, $file, $prefix . $type, $rev);
          if ($response->ok) {
            if ($erase) {
              $this->erase($file);
            }
            $rev = $response->rev;
          }
        } catch (\Exception $ex) {
          $this->logException($ex);
        }
      }
    }
  }

  public function detach($model, $id, $name) {
    $doc = $this->_dom()->locate($model, $id);
    if (empty($doc)) {
      return false;
    }
    if (isset($doc->_attachments->$name)) {
      unset($doc->_attachments->$name);
      $this->_dom()->update($model, (array)$doc);
    }

    return true;
  }

  public function detachAll($model, $id, $prefix) {
    $doc = $this->_dom()->locate($model, $id);
    if (empty($doc)) {
      return false;
    }
    foreach ($doc->_attachments as $name => $value) {
      if (strpos($name, $prefix) === 0) {
        unset($doc->_attachments->$name);
      }
    }
    $this->_dom()->update($model, (array)$doc);

    return true;
  }

  public function file($model, $id, $name, $path = null) {
    $doc = $this->_dom()->locate($model, $id);
    if (empty($doc)) {
      return false;
    }
    if (!isset($doc->_attachments->$name)) {
      return false;
    }
    $att = $doc->_attachments->$name;

    $ext = ExtensionGuesser::getInstance()->guess($att->content_type);

    if (empty($path)) {
      $path = $this->temporary($ext);
    } else {
      $path = self::ensureDir($path);
      $path .= DIRECTORY_SEPARATOR . $name . '.' . $ext;
    }

    $src = $this->_dom()->open($model, $id, $name)->getRealPath();
    copy($src, $path);

    $file = new File($path);

    return $file;
  }

  protected function _link($model, $id, $name, $absolute = false) {
    return $this->_router()->generate(
      'beats.fsal.link', array(
      'id'   => DOM::domID($model, $id),
      'name' => $name,
    ), $absolute
    );
  }

  public function link($model, $id, $name, $absolute = false) {
    $cacheID = $this->_id(func_get_args());
    try {
      return $this->_cacheLoad($cacheID, __FUNCTION__);
    } catch (CacheException $ex) {
    }
    $href = $this->_link($model, $id, $name, $absolute);

    return $this->_cacheSave($cacheID, __FUNCTION__, $href);
  }


  protected function _exists($model, $id, $name) {
    $doc = $this->_dom()->locate($model, $id);

    return !empty($doc) && isset($doc->_attachments->$name);
    //    $href = $this->link($model, $id, $name, true);
    ////    $code    = false;
    ////    $context = stream_context_create(array(
    ////      'http' => array(
    ////        'method'          => "HEAD",
    ////        'follow_location' => 0
    ////      )
    ////    ));
    ////    if (file_get_contents($href, null, $context) && !empty($http_response_header)) {
    ////      sscanf($http_response_header[0], 'HTTP/%*d.%*d %d', $code);
    ////    }
    //    list($status) = get_headers($href);
    //    /** @noinspection PhpUnusedLocalVariableInspection */
    //    list($protocol, $code, $message) = explode(' ', $status);
    //    return $code == 200
  }

  public function exists($model, $id, $name) {
    $cacheID = $this->_id(func_get_args());
    try {
      return $this->_cacheLoad($cacheID, __FUNCTION__);
    } catch (CacheException $ex) {
    }

    $exists = $this->_exists($model, $id, $name);

    return $this->_cacheSave($cacheID, __FUNCTION__, $exists);
  }

  /********************************************************************************************************************/

  private $_cache = array();

  private function _id(array $args) {
    return implode('-', $args);
  }

  private function _cacheSave($id, $method, $value) {
    if (!isset($this->_cache[$method])) {
      $this->_cache[$method] = array();
    }

    return $this->_cache[$method][$id] = $value;
  }

  private function _cacheLoad($id, $method) {
    if (isset($this->_cache[$method]) && isset($this->_cache[$method][$id])) {
      return $this->_cache[$method][$id];
    }
    throw new CacheException();
  }

}

class CacheException extends Exception {
}
