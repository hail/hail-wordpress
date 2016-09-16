<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken as AccessToken;
use Psr\Http\Message\ResponseInterface as ResponseInterface;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;

class HailProvider extends AbstractProvider {

  use BearerAuthorizationTrait;

  const BASE_HAIL_URL = 'https://hail.to/';
  const BASE_HAIL_DEV_URL = 'https://dev.hail.to/';

  protected $devMode = false;

  public function __construct($options = [], array $collaborators = []) {
    parent::__construct($options, $collaborators);

    if (!empty($options['devMode']) && $options['devMode'] === true) {
      $this->devMode = true;
    }
  }

  private function getBaseHailUrl() {
    return $this->devMode ? static::BASE_HAIL_DEV_URL : static::BASE_HAIL_URL;
  }

  public function getBaseAuthorizationUrl() {
    return $this->getBaseHailUrl() . 'oauth/authorise';
  }

  public function getBaseAccessTokenUrl(array $params) {
    return $this->getBaseHailUrl() . 'api/v1/oauth/access_token';
  }

  public function getResourceOwnerDetailsUrl(AccessToken $token) {
    return $this->getBaseHailUrl() . 'api/v1/me';
  }

  protected function getDefaultScopes() {
    return ['content.read user.basic'];
  }

  protected function checkResponse(ResponseInterface $response, $data) {
    // check for errors in response and throw an exception
  }

  protected function createResourceOwner(array $response, AccessToken $token) {
    return new HailUser($response);
  }

}
