<?php
namespace BeatsBundle\Service;

use BeatsBundle\Service\Mailer\Mail;

class Mailer extends ContainerAware {

  /**
   * @return \Swift_Mailer
   */
  private function _swift() {
    return $this->container->get('mailer');
  }

  /*********************************************************************************************************************/

  protected function _mails($type = 'default') {
    $mails = $this->_options->get('mails');
    if (empty($type)) {
      return $mails;
    }
    if (empty($mails)) {
      return null;
    }
    if (empty($mails[$type])) {
      $type = 'default';
    }
    return $mails[$type];
  }

  /*********************************************************************************************************************/

  /**
   * @param string      $mail
   * @param string|null $name
   * @return array
   */
  public function toAddress($mail, $name = null) {
    $default = (object)$this->_mails();
    return array((empty($mail) ? $default->mail : $mail) => (empty($name) ? $default->name : $name));
  }

  /**
   * @param $type
   * @return array|null
   */
  public function getMail($type) {
    if (is_string($type)) {
      if (strpos($type, '@')) { // LATER@ion:Create a better email check
        $mail = array('mail' => $type);
      } else {
        $mail = $this->_mails($type);
      }
    } elseif (is_array($type)) {
      $mail = $type;
    } else {
      $mail = $this->_mails();
    }
    if (empty($mail)) {
      return $mail;
    }
    $mail = (object)array_merge(array(
      'mail' => null,
      'name' => null,
    ), $mail);
    return $this::toAddress($mail->mail, $mail->name);
  }

  /**
   * @return array
   */
  public function getMails() {
    return $this->_mails(false);
  }

  /*********************************************************************************************************************/

  /**
   * @param Mail $mail
   * @param array $failed
   * @return int The number of mails sent
   */
  public function send(Mail $mail, array &$failed = null) {
    return $this->_swift()->send($mail, $failed);
  }

  /**
   * @param $from
   * @param $to
   * @param $subject
   * @param $message
   * @param bool $mime
   * @param array $failed
   * @return int The number of mails sent
   */
  public function post($from, $to, $subject, $message, $mime = true, array &$failed = null) {

    if (!is_string($mime)) {
      $mime = empty($mime) ? 'text/plain' : 'text/html';
    }
    $mail = new Mail($subject, $message, $mime);

    $mail->setFrom($this->getMail($from));
    $mail->setTo($this->getMail($to));

    return $this->send($mail, $failed);
  }

  /*********************************************************************************************************************/

}





