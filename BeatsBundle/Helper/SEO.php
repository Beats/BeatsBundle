<?php
namespace BeatsBundle\Helper;

class SEO {

  /**
   * @param $text
   * @param bool $trim
   * @param string $empty
   * @return string
   */
  static public function slugify($text, $trim = true, $empty = 'n-a') {
    if ($trim) {
      $text = trim($text);
    }

    if (empty($text)) {
      return $empty;
    }

    // replace non letter or digits by -
    $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

    // trim
    $text = trim($text, '-');

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // lowercase
    $text = strtolower($text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    if (empty($text)) {
      return $empty;
    }

    return $text;
  }

}
