<?php
namespace BeatsBundle\FSAL;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

class Image {

  protected $_path;
  protected $_resource;

  static protected $_orientationToDeg = array(
    1 => 0,
    2 => 0,
    3 => 180,
    4 => 180,
    5 => -90,
    6 => -90,
    7 => 90,
    8 => 90,
  );


  public function __construct($path) {
    $this->_path = $path instanceof \SplFileInfo ? $path->getRealPath() : $path;

    $info = getimagesize($path);
    if (empty($info)) {
      throw new \BeatsBundle\Exception\Exception("Invalid image path [$path]");
    }
    $this->_width    = $info[0];
    $this->_height   = $info[1];
    $this->_type     = $info[2];
    $this->_mime     = $info['mime'];
    $this->_bits     = $info['bits'];
    $this->_channels = isset($info['channels']) ? $info['channels'] : null;
  }

  public function __destruct() {
    $this->destroy();
  }

  public function getPath() {
    return $this->_path;
  }

  public function getResource() {
    if (!is_resource($this->_resource)) {
      $this->_resource = self::openImage($this->getPath(), $this->_type);
    }

    return $this->_resource;
  }

  public function getWidth() {
    return $this->_width;
  }

  public function getHeight() {
    return $this->_height;
  }

  public function getMime() {
    return $this->_mime;
  }

  public function getType() {
    return $this->_type;
  }

  /**
   * @param null $param
   * @return array|null
   */
  public function getExif($param = null) {
    $exif = exif_read_data($this->getPath(), 'EXIF');
    if (empty($param)) {
      return $exif;
    }
    if (array_key_exists($param, $exif)) {
      return $exif[$param];
    }

    return null;
  }

  /**
   * Group IFD0
   *
   * This is the actual orientation schematics.
   * For now we only rotate, but mirroring will be added if needed
   *
   * 1 = Horizontal (normal)
   * 2 = Mirror horizontal
   * 3 = Rotate 180
   * 4 = Mirror vertical
   * 5 = Mirror horizontal and rotate 270 CW
   * 6 = Rotate 90 CW
   * 7 = Mirror horizontal and rotate 90 CW
   * 8 = Rotate 270 CW
   * @param null $orientation
   * @return array|bool|null
   */
  public function getRotation($orientation = null) {
    $orientation = $orientation === 'true' ? true : ($orientation === 'false' ? false : $orientation);
    $orientation = $orientation == 0 ? null : $orientation;
    if (!empty($orientation)) {
      if (is_bool($orientation) && $orientation) {
        $orientation = $this->getExif('Orientation');
      }
      if (empty($orientation)) {
        return 0;
      }

      return self::$_orientationToDeg[$orientation];
    }

    return null;
  }

  public function getAspectRatio() {
    return self::aspectRatio($this->_width, $this->_height);
  }

  public function getExtension() {
    return self::extension($this->getMime());
  }

  public function hasResource() {
    return !empty($this->_resource);
  }

  public function destroy() {
    if ($this->hasResource()) {
      imagedestroy($this->_resource);
      $this->_resource = null;
    }
  }

  static public function aspectRatio($width, $height) {
    return empty($height) ? 0 : round($width / $height, 2);
  }


  static public function openImage($path, $type) {
    switch ($type) {
      case IMAGETYPE_GIF:
        return imagecreatefromgif($path);
      case IMAGETYPE_JPEG:
        return imagecreatefromjpeg($path);
      case IMAGETYPE_PNG:
        return imagecreatefrompng($path);
      default:
        throw new \BeatsBundle\Exception\Exception("Unsupported image type");
    }
  }

  static public function mime($type) {
    return image_type_to_mime_type($type);
  }

  static public function extension($type) {
    return ExtensionGuesser::getInstance()->guess(self::mime($type));
  }

  static public function saveImage($resource, $type, $path = null, $quality = 80) {
    $fext = self::extension($type);
    $info = pathinfo($path);
    if (empty($info['extension']) || $info['extension'] != $fext) {
      $path = $info['dirname'] . DIRECTORY_SEPARATOR . $info['basename'] . '.' . $fext;
    }
    switch ($type) {
      case IMAGETYPE_GIF:
        return imagegif($resource, $path);
      case IMAGETYPE_JPEG:
        return imagejpeg($resource, $path, $quality);
      case IMAGETYPE_PNG:
        imagesavealpha($resource, true);

        return imagepng($resource, $path, ($quality / 100) % 10);
      default:
        throw new \BeatsBundle\Exception\Exception("Unsupported image type");
    }
  }


}

