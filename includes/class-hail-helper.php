<?php

/**
 * Define the Hail helper functions for OAuth
 *
 * @link          http://get.hail.to
 * @since         1.0.0
 *
 * @package       Hail
 * @subpackage    Hail/includes
 */

use GuzzleHttp\Psr7\Request;

class Hail_Helper {

  private $plugin_name;

  private $provider;
  private $hailBaseURI = 'https://dev.hail.to/';
  // private $clientID;
  // private $clientSecret;
  private $config;

  public function __construct($plugin_name) {
    $this->plugin_name = $plugin_name;

    $this->config = get_option($this->plugin_name);

    $this->provider = new League\OAuth2\Client\Provider\HailProvider([
      'clientId'                => $this->config['client_id'],    // The client ID assigned to you by the provider
      'clientSecret'            => $this->config['client_secret'],   // The client password assigned to you by the provider
      'redirectUri'             => 'http://dev.vlnprimary.school.nz/wp/wp-admin/admin.php?page=hail&action=verify',
      'urlAuthorize'            => $this->hailBaseURI . 'oauth/authorise',
      'urlAccessToken'          => $this->hailBaseURI . 'api/v1/oauth/access_token',
      'urlResourceOwnerDetails' => $this->hailBaseURI . 'api/v1/me',
      'devMode'                 => true
    ]);

    $this->guzzle = new GuzzleHttp\Client();
    // $this->guzzle->setDefaultHeaders(
    //   array(
    //
    //   )
    // );

    $this->predis = new Predis\Client();

  }

  public function getClientID() {
    return array_key_exists('client_id', $this->config) ? $this->config['client_id'] : '';
  }

  public function getClientSecret() {
    return array_key_exists('client_secret', $this->config) ? $this->config['client_secret'] : '';
  }

  public function getRedisEnabled() {
    return array_key_exists('redis_enabled', $this->config) ? $this->config['redis_enabled'] : 0;
  }

  public function getAuthorizationUrl() {
    return $this->provider->getAuthorizationUrl();
  }

  public function getAccessToken($type, $data) {
    return $this->provider->getAccessToken($type, $data);
  }

  // always a GET call
  private function call($url) {

    // TODO: a way of testing whether or not redis exists and is working

    // create an hash of the URL being requested
    // look up the hash in the cache (have different cache providers?)
    // if matched then return cached results
    // if not matched then proceed to make request and then store result against hash

    $hash = sha1($url);
    $cached_result_body = $this->predis->get($hash);

    if ($cached_result_body) {
      return json_decode($cached_result_body, true);
    }


    $access_token = get_option('hail-access_token');
    $refresh_token = get_option('hail-refresh_token');
    $expires = get_option('hail-expires');

    $token = new League\OAuth2\Client\Token\AccessToken([
      'access_token' => $access_token,
      'refresh_token' => $refresh_token,
      'expires' => $expires
    ]);

    // $request = $provider->getAuthenticatedRequest(
    //   'GET',
    //   'http://brentertainment.com/oauth2/lockdin/resource',
    //   $accessToken
    // );

    // error_log('existing token: ' . var_export($token, true));

    if ($token->hasExpired()) {
      // error_log('token is expired');

      $newToken = $this->provider->getAccessToken('refresh_token', [
        'refresh_token' => $token->getRefreshToken()
      ]);

      // error_log('new token: ' . var_export($newToken, true));

      $token = $newToken;

      update_option('hail-access_token', $newToken->getToken());
      update_option('hail-refresh_token', $newToken->getRefreshToken());
      update_option('hail-expires', $newToken->getExpires());

    }


    $headers = array(
      'Authorization' => 'Bearer ' . $token->getToken()
    );

    $request = new Request('GET', $url, $headers);

    $result_body = $this->guzzle->send($request)->getBody();

    $json = json_decode($result_body, true);

    $this->predis->set($hash, $result_body, 'ex', 60);

    return $json;
  }

  public function test() {
    return $this->call($this->hailBaseURI . 'api/v1/me');
  }

  // TODO:
  // query functions such as get articles by tag ID?

}
