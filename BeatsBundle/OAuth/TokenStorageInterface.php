<?php

namespace BeatsBundle\OAuth;

interface TokenStorageInterface {

  /**
   * Fetch a request token from the storage.
   *
   * @param ResourceProviderInterface $provider
   * @param string                 $tokenID
   *
   * @return array
   */
  public function fetch(ResourceProviderInterface $provider, $tokenID);

  /**
   * Save a request token to the storage.
   *
   * @param ResourceProviderInterface $provider
   * @param array                  $token
   */
  public function save(ResourceProviderInterface $provider, array $token);

}
