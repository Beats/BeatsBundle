<?php

namespace BeatsBundle\Security\User;

interface InfoInterface {

  /**
   * Get the access token used for the request.
   *
   * @return mixed
   */
  public function getAccessToken();

  /**
   * Set the access token used for the request.
   *
   * @param mixed $accessToken
   */
  public function setAccessToken($accessToken);

  /**
   * Get the user info provider
   *
   * @return string
   */
  public function getProvider();

  /********************************************************************************************************************/

  /**
   * Get the unique user identifier.
   *
   * @return string
   */
  public function getID();

  /**
   * Get the email address.
   *
   * @return null|string
   */
  public function getEmail();

  /**
   * Get the username
   * @return string
   */
  public function getUsername();

  /********************************************************************************************************************/

  /**
   * Get the username to display.
   *
   * @return string
   */
  public function getNameFirst();

  /**
   * Get the real name of user.
   *
   * @return string
   */
  public function getNameLast();

  /**
   * Get the user birthday as a timestamp
   *
   * @return string
   */
  public function getDOB();

  /********************************************************************************************************************/

  /**
   * Get the url to the profile picture.
   *
   * @return null|string
   */
  public function getAvatar();

  /**
   * Returns true if the user info has a valid avatar url
   *
   * @return boolean
   */
  public function hasAvatar();

  /********************************************************************************************************************/

  /**
   * Returns the user registered locale
   *
   * @return string
   */
  public function getLocale();

  /**
   * Returns the user registered timezone
   *
   * @return string
   */
  public function getTimezone();

}
