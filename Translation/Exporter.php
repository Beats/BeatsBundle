<?php
namespace BeatsBundle\Translation;


use BeatsBundle\Service\WarmableContainerAware;

class Exporter extends WarmableContainerAware {

  const SCRIPT_VARS = 'translations';
  const SCRIPT_FILE = 'translation.%s.js';
  const CACHED_FILE = 'translations.php.meta';

  /**
   * @var array
   */
  private $_translations;

  /**
   * @return array
   */
  public function getTranslations() {
    if (empty($this->_translations)) {
      $this->_translations = $this->_build();
    }
    return $this->_translations;
  }

  /*********************************************************************************************************************/

  /**
   * Warms up the cache.
   *
   * @param string $cacheDir The cache directory
   *
   * @return array
   */
  public function warmUp($cacheDir) {
    $translations = $this->getTranslations();

    $this->_cachePersister()->store($translations, self::CACHED_FILE, $cacheDir);

    $this->_export($translations);

    return $translations;
  }

  public function isOptional() {
    return false;
  }

  /*********************************************************************************************************************/

  protected function _build() {
    $translator = $this->_translator();

    $translations = array();
    foreach ($translator->getLocales() as $locale => $data) {
      $translations[$locale] = $translator->export('en');
    }

    return $translations;
  }

  protected function _export($translations, $file = self::SCRIPT_FILE, $vars = self::SCRIPT_VARS) {
    foreach ($translations as $locale => $map) {
      $path = implode(DIRECTORY_SEPARATOR, array(
        dirname($this->_kernel()->getRootDir()), 'web', 'translations', sprintf($file, $locale)
      ));
      $home = dirname($path);
      if (!is_dir($home) && !mkdir($home, 0777, true)) {
        throw new \RuntimeException("Could not create a translation definition directory: $home");
      }
//      $content = sprintf("var %s['%s'] = %s;", $vars, $locale, json_encode($map, JSON_PRETTY_PRINT));
      $content = sprintf("var %s = %s;", $vars, json_encode($map, JSON_PRETTY_PRINT));
      if (file_put_contents($path, $content) === false) {
        throw new \RuntimeException("Could not create a translation definition file: $path");
      }

    }
  }


  /*********************************************************************************************************************/

}





