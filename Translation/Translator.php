<?php
namespace BeatsBundle\Translation;


class Translator extends \Symfony\Bundle\FrameworkBundle\Translation\Translator {

  /**
   * @param string $locale
   * @param string $domain
   *
   * @return array
   */
  public function export($locale, $domain = 'messages') {
    if (!isset($this->catalogues[$locale])) {
      $this->loadCatalogue($locale);
    }
    return $this->catalogues[$locale]->all($domain);
  }

  public function getLocales() {
    return $this->container->getParameter('beats.translation.locales');
  }

}





