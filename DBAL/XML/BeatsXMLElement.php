<?php
/**
 * Created by PhpStorm.
 * User: ion
 * Date: 6/6/14
 * Time: 10:15 AM
 */

namespace BeatsBundle\DBAL\XML;

use Symfony\Component\DependencyInjection\SimpleXMLElement;

class BeatsXMLElement extends SimpleXMLElement {

  /**
   * @param string $value
   *
   * @return $this
   */
  public function setCData($value) {
    $node = dom_import_simplexml($this);
    foreach ($node->childNodes as $child) {
      $node->removeChild($child);
    }
    $node->appendChild($node->ownerDocument->createCDATASection($value));
    return $this;
  }

  /**
   * @param \SimpleXMLElement $that
   *
   * @return $this
   */
  public function replace(\SimpleXMLElement $that) {
    $selfDOM = dom_import_simplexml($this);
    $nodeDOM = $selfDOM->ownerDocument->importNode(dom_import_simplexml($that), true);
    $selfDOM->parentNode->replaceChild($nodeDOM, $selfDOM);
    return $this;
  }

  /**
   * @param string $path
   *
   * @return BeatsXMLElement
   */
  public function xpathOne($path) {
    $els = $this->xpath($path);
    if (empty($els)) {
      return $els;
    }
    return reset($els);
  }

}