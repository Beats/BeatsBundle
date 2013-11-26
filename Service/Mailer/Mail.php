<?php
namespace BeatsBundle\Service\Mailer;

class Mail extends \Swift_Message {

  public function setMessage($body, $contentType = null, $charset = null) {
    return parent::setBody($body, $contentType, $charset);
  }

}