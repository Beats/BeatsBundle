<?php
namespace BeatsBundle\Newsletter;

interface NewsletterInterface {

  /**
   * Subscribe an email to a newsletter
   * @param mixed $newsletter
   * @param string $email
   * @return bool
   */
  public function subscribe($newsletter, $email);

  /**
   * @param $newsletter
   * @param $email
   * @return bool
   */
  public function unsubscribe($newsletter, $email);

}