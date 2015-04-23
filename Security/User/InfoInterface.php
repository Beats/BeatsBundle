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

}
