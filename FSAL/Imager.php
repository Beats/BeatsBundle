<?php
namespace BeatsBundle\FSAL;

use BeatsBundle\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class Imager extends ContainerAware {

  const KEEP_AR_NONE  = 0;
  const KEEP_AR_OUTER = 1;
  const KEEP_AR_INNER = -1;

  public function __construct(ContainerInterface $container) {
    $this->setContainer($container);
  }

  /**
   * @return AbstractFSAL
   */
  final protected function _fsal() {
    return $this->container->get('beats.fsal.domfs');
  }

  protected function _toPath($path) {
    if (is_string($path)) {
      return $path;
    } elseif ($path instanceof \SplFileInfo) {
      return $path->getRealPath();
    }
    throw new Exception("Path, URL or SplFileInfo expected");
  }

  protected function _toResource($path, $type) {
    $path = $this->_toPath($path);
    switch ($type) {
      case IMAGETYPE_GIF:
        return imagecreatefromgif($path);
      case IMAGETYPE_JPEG:
        return imagecreatefromjpeg($path);
      case IMAGETYPE_PNG:
        return imagecreatefrompng($path);
      default:
        throw new Exception("Unsupported image type");
    }
  }

  public function getImage($path) {
    $info = $this->getImageInfo($path);
    return $this->_toResource($path, $info->type);
  }

  public function getImageInfo($path) {
    $info = getimagesize($this->_toPath($path));
    return (object)array(
      'width'    => $info[0],
      'height'   => $info[1],
      'type'     => $info[2],
      'mime'     => $info['mime'],
      'bits'     => $info['bits'],
      'channels' => $info['channels'],
    );
  }

  public function dimensions($src) {
    return array(imagesx($src), imagesy($src));
  }

  public function blank($w, $h, $transparent = true) {
    $dst = imagecreatetruecolor($w, $h);
    if ($transparent) {
      imagealphablending($dst, false);
      $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
      imagefilledrectangle($dst, 0, 0, $w, $h, $transparent);
    }
    return $dst;
  }

  public function crop($src, $srcTLX, $srcTLY, $srcBRX, $srcBRY) {
    if ($srcTLX > $srcBRX || $srcTLY > $srcBRY) {
      return false;
    }
    $dstW = $srcBRX - $srcTLX;
    $dstH = $srcBRY - $srcTLY;
    $dst  = $this->blank($dstW, $dstH);

    imagecopyresampled($dst, $src, 0, 0, $srcTLX, $srcTLY, $dstW, $dstH, $dstW, $dstH);

    return $dst;
  }

  public function rotate($src, $deg) {
    if ($deg == 0) {
      return $src;
    }
    $dst = imagerotate($src, $deg, 0);
    return $dst;
  }

  public function resize($src, $srcW, $srcH, $dstW, $dstH, $keepAR = self::KEEP_AR_NONE) {
    $srcAR = Image::aspectRatio($srcW, $srcH);
    $dstAR = Image::aspectRatio($dstW, $dstH);

    $dst = $this->blank($dstW, $dstH);

    if (empty($keepAR) or ($srcAR == $dstAR)) {
      imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
    } else {

      if ($keepAR > 0) {
        if ($srcAR > $dstAR) {
          $tmpW = (int)($dstW);
          $tmpH = (int)($dstW / $srcAR);
        } else {
          $tmpW = (int)($dstH * $srcAR);
          $tmpH = (int)($dstH);
        }

        $dstX = (int)(abs($tmpW - $dstW) / 2);
        $dstY = (int)(abs($tmpH - $dstH) / 2);
        $tmpX = 0;
        $tmpY = 0;
      } else {
        if ($srcAR > $dstAR) {
          $tmpW = (int)($dstH * $srcAR);
          $tmpH = (int)($dstH);
        } else {
          $tmpW = (int)($dstW);
          $tmpH = (int)($dstW / $srcAR);
        }
        $dstX = 0;
        $dstY = 0;
        $tmpX = (int)(abs($tmpW - $dstW) / 2);
        $tmpY = (int)(abs($tmpH - $dstH) / 2);
      }
      // Scale image
      $tmp = imagecreatetruecolor($tmpW, $tmpH);
      imagecopyresampled($tmp, $src, 0, 0, 0, 0, $tmpW, $tmpH, $srcW, $srcH);

      imagecopy($dst, $tmp, $dstX, $dstY, $tmpX, $tmpY, $tmpW, $tmpH);
      imagedestroy($tmp);
    }

    return $dst;
  }

  public function scaleXY($srcX, $srcY, $srcW, $srcH, $dstW, $dstH) {
    $rW = Image::aspectRatio($srcW, $dstW);
    $rH = Image::aspectRatio($srcH, $dstH);

    $dstX = (int)($srcX * $rW);
    $dstY = (int)($srcY * $rH);

    return array($dstX, $dstY);
  }

  public function scaleWH($srcW, $srcH, $dstW, $dstH, $scale = null) {
    if (empty($scale)) {
      if ($dstW * $dstH) {
        return array($dstW, $dstH);
//        throw new Exception("Both destination width and height were set [$dstW, $dstH]");
      }
      if ($dstW | $dstH) {
        $srcAR = Image::aspectRatio($srcW, $srcH);
        if (empty($dstH)) {
          $dstH = (int)($dstW / $srcAR);
        } else {
          $dstW = (int)($dstH * $srcAR);
        }
      } else {
        throw new Exception("Both destination width and height were 0");
      }
    } else {
      $dstW = (int)($srcW * $scale);
      $dstH = (int)($srcH * $scale);
    }
    return array($dstW, $dstH);
  }

  public function resizeCrop($src, $srcTLX, $srcTLY, $srcBRX, $srcBRY, $dstW, $dstH, $keepAR = self::KEEP_AR_NONE) {
    $tmp = $this->crop($src, $srcTLX, $srcTLY, $srcBRX, $srcBRY);
    return $this->resize($tmp, $srcBRX - $srcTLX, $srcBRY - $srcTLY, $dstW, $dstH, $keepAR);
  }

  public function maxResolution(array $dimensions) {
    return array_reduce($dimensions, function (&$result, $item) {
      if (empty($result)) {
        return $item;
      }
      return array_product($item) > array_product($result) ? $item : $result;
    });
  }

  protected function _cleanup(array $parameters) {
    return array_map(function ($item) {
      return $item | 0;
    }, $parameters);
  }

  /**
   * @param $src
   * @param array $dstDimensions
   * @param int $keepAR
   * @param array $crop
   * @param float $rotateDeg
   * @return array
   * @throws Exception
   */
  public function extract($src, array $dstDimensions, $keepAR = self::KEEP_AR_NONE, array $crop = null, $rotateDeg = null) {
    if (empty($rotateDeg)) {
      $rotated = $src;
    } else {
      $rotated = $this->rotate($src, $rotateDeg);
    }

    list($srcW, $srcH) = $this->dimensions($rotated);
    list($dstW, $dstH) = $this->maxResolution($dstDimensions);
    list($dstW, $dstH) = $this->scaleWH($srcW, $srcH, $dstW, $dstH);

    if (empty($crop)) {
      $temp = $this->resize($rotated, $srcW, $srcH, $dstW, $dstH, $keepAR);
    } else {
      list($srcTLX, $srcTLY, $srcBRX, $srcBRY) = $this->_cleanup($crop);
      $temp = $this->resizeCrop($rotated, $srcTLX, $srcTLY, $srcBRX, $srcBRY, $dstW, $dstH, $keepAR);
    }

    if (!empty($rotateDeg)) {
      imagedestroy($rotated);
    }

    $images = array();
    foreach ($dstDimensions as $name => $dst) {
      list($w, $h) = $this->scaleWH($dstW, $dstH, $dst[0], $dst[1]);
      $avatar = $this->resize($temp, $dstW, $dstH, $w, $h, $keepAR);
      $path   = $this->_fsal()->temporary(Image::extension(IMAGETYPE_JPEG), $name . '_');
      if (!Image::saveImage($avatar, IMAGETYPE_JPEG, $path)) {
        throw new Exception("Failed to save image to [$path]");
      }
      $images[$name] = new File($path);
    }

    imagedestroy($temp);

    return $images;
  }

  public function scale($src, $dstW, $dstH = 0, $scale = null) {
    list($srcW, $srcH) = $this->dimensions($src);
    list($dstW, $dstH) = $this->scaleWH($srcW, $srcH, $dstW, $dstH, $scale);
    $path  = $this->_fsal()->temporary(Image::extension(IMAGETYPE_JPEG), 'o_');
    $image = $this->resize($src, $srcW, $srcH, $dstW, $dstH);
    if (!Image::saveImage($image, IMAGETYPE_JPEG, $path)) {
      throw new Exception("Failed to save image to [$path]");
    }
    return new File($path);
  }


}

