<?php

namespace BeatsBundle\Security\User\Info;

use BeatsBundle\Helper\UTC;
use BeatsBundle\Security\User\InfoInterface;

class BaseInfo implements InfoInterface {

  static public function toUsername($email, array $data = null, $field = null) {
    if (empty($field) || empty($data) || empty($data[$field])) {
      return substr($email, 0, strpos($email, '@'));
    } else {
      return $data[$field];
    }
  }

  static public function toNames($username, array $data, $fldName = null, $fldFName = null, $fldLName = null) {
    $names = explode(' ', (empty($fldName) || empty($data[$fldName])) ? $username : $data[$fldName], 2);
    if (count($names) < 2) {
      $names[] = '';
    }
    if (!(empty($fldFName) || empty($data[$fldFName]))) {
      $names[0] = $data[$fldFName];
    }
    if (!(empty($fldLName) || empty($data[$fldLName]))) {
      $names[1] = $data[$fldLName];
    }
    return $names;
  }

  protected $_accessToken;
  protected $_data;


  protected $_id;
  protected $_email;
  protected $_username;
  protected $_name_f;
  protected $_name_l;
  protected $_dob;
  protected $_avatar;
  protected $_locale;
  protected $_timezone;

  /********************************************************************************************************************/

  public function __construct($accessToken, $provider, array $data,
                              $id = null, $email = null, $username = null,
                              $name_f = null, $name_l = null, $dob = null,
                              $avatar = null, $locale = null, $timezone = null
  ) {
    $this->_accessToken = $accessToken;
    $this->_provider    = $provider;
    $this->_data        = $data;

    $this->_id       = $id;
    $this->_email    = $email;
    $this->_username = $username;

    $this->_name_f = $name_f;
    $this->_name_l = $name_l;
    $this->_dob    = empty($dob) ? null : UTC::toDate($dob);

    $this->_avatar   = $avatar;
    $this->_locale   = $locale;
    $this->_timezone = $timezone;
  }

  /********************************************************************************************************************/

  /**
   * Get the access token used for the request.
   *
   * @return mixed
   */
  public function getAccessToken() {
    return $this->_id;
  }

  /**
   * Set the access token used for the request.
   *
   * @param mixed $accessToken
   */
  public function setAccessToken($accessToken) {
    $this->_accessToken = $accessToken;
  }

  /**
   * Get the user info provider
   *
   * @return string
   */
  public function getProvider() {
    return $this->_provider;
  }

  /********************************************************************************************************************/

  /**
   * @return array
   */
  public function getData() {
    return $this->_data;
  }

  /********************************************************************************************************************/

  /**
   * Get the unique user identifier.
   *
   * @return string
   */
  public function getID() {
    return $this->_id;
  }


  /**
   * Get the email address.
   *
   * @return null|string
   */
  public function getEmail() {
    return $this->_email;
  }

  /**
   * Get the username
   * @return string
   */
  public function getUsername() {
    return $this->_username;
  }


  /**
   * Get the username to display.
   *
   * @return string
   */
  public function getNameFirst() {
    return $this->_name_f;
  }

  /**
   * Get the real name of user.
   *
   * @return string
   */
  public function getNameLast() {
    return $this->_name_l;
  }

  /**
   * Get the birthday
   *
   * @return string
   */
  public function getDOB() {
    return $this->_dob;
  }


  /**
   * Get the url to the profile picture.
   *
   * @return null|string
   */
  public function getAvatar() {
    return $this->_avatar;
  }

  /**
   * Returns true if the user info has a valid avatar url
   *
   * @return boolean
   */
  public function hasAvatar() {
    return !empty($this->_avatar);
  }


  /**
   * Returns the user registered locale
   *
   * @return string
   */
  public function getLocale() {
    return $this->_locale;
  }

  /**
   * Returns the user registered timezone
   *
   * @return string
   */
  public function getTimezone() {
    return $this->_timezone;
  }

  /********************************************************************************************************************/

}