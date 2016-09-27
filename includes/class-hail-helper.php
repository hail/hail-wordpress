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
use Carbon\Carbon;

class Hail_Helper {

  private $plugin_name;

  private $provider;
  private $hailBaseURI = 'https://dev.hail.to/';
  // private $clientID;
  // private $clientSecret;
  private $config;

  private static $instance;

  function __construct($plugin_name) {
    $this->plugin_name = $plugin_name;

    $this->config = get_option($this->plugin_name) ?: array();



    $this->guzzle = new GuzzleHttp\Client();
    // $this->guzzle->setDefaultHeaders(
    //   array(
    //
    //   )
    // );

    $this->predis = new Predis\Client();

    error_log('testing for redis');
    try {
      $this->predis->ping();
    } catch (Predis\Connection\ConnectionException $e) {
      $this->predis = false;
    }

  }

  private function initProvider() {
    if (!array_key_exists('client_id', $this->config) || !array_key_exists('client_secret', $this->config)) {
      $this->provider = false;
      return;
    }

    $this->provider = new League\OAuth2\Client\Provider\HailProvider([
      'clientId'                => $this->config['client_id'],    // The client ID assigned to you by the provider
      'clientSecret'            => $this->config['client_secret'],   // The client password assigned to you by the provider
      'redirectUri'             => 'http://dev.vlnprimary.school.nz/wp/wp-admin/admin.php?page=hail&action=verify',
      'urlAuthorize'            => $this->hailBaseURI . 'oauth/authorise',
      'urlAccessToken'          => $this->hailBaseURI . 'api/v1/oauth/access_token',
      'urlResourceOwnerDetails' => $this->hailBaseURI . 'api/v1/me',
      'devMode'                 => true
    ]);
  }

  public static function getInstance() {
    if (!isset(self::$instance)) {
      self::$instance = new self('hail');
    }

    return self::$instance;
  }

  public function toAdminUrlDefault() {
    $url = admin_url('admin.php?page=' . $this->plugin_name);

    if (headers_sent()) {
      echo die('<script type="text/javascript">window.location.href = "' . $url . '";</script>');
    } else {
      header('Location: ' . $url);
      die();
    }

    // if(headers_sent())
    // {
    //   $destination = ($url == false ? 'location.reload();' : 'window.location.href="' . $url . '";');
    //   echo die('<script>' . $destination . '</script>');
    // }
    // else
    // {
    //   $destination = ($url == false ? $_SERVER['REQUEST_URI'] : $url);
    //   header('Location: ' . $destination);
    //   die();
    // }
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

  public function getPrimaryPtag() {
    return array_key_exists('primary_ptag', $this->config) ? $this->config['primary_ptag'] : '';
  }

  public function getAuthorizationUrl() {
    return $this->provider ? $this->provider->getAuthorizationUrl() : null;
  }

  public function getAccessToken($type, $data) {
    return $this->provider ? $this->provider->getAccessToken($type, $data) : null;
  }



  // always a GET call
  private function call($url, $cache = true) {

    if (!$this->predis) $cache = false;

    if (!$this->provider) {
      $this->initProvider();
    }

    if (!$this->provider) {
      throw new Exception('Not enough data was configured to instantiate the provider');
    }

    // TODO: a way of testing whether or not redis exists and is working

    // create an hash of the URL being requested
    // look up the hash in the cache (have different cache providers?)
    // if matched then return cached results
    // if not matched then proceed to make request and then store result against hash

    if ($cache) {
      $hash = sha1($url);
      $cached_result_body = $this->predis->get($hash);

      if ($cached_result_body) {
        return json_decode($cached_result_body, true);
      }
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

    if ($cache) {
      $this->predis->set($hash, $result_body, 'ex', 60);
    }

    return $json;
  }

  public function test() {
    return $this->call($this->hailBaseURI . 'api/v1/me', false);
  }

  public function getArticle($id) {
    return $this->call($this->hailBaseURI . 'api/v1/articles/' . $id);
  }

  public function getArticlesByPrivateTag($id, $cache = true) {
    // https://dev.hail.to/api/v1/private-tags/58kHKvj/articles?limit=50&order=date%7Cdesc&offset=0
    return $this->call($this->hailBaseURI . 'api/v1/private-tags/' . $id . '/articles', $cache);
  }

  public function getArticleImages($id, $cache = true) {
    return $this->call($this->hailBaseURI . 'api/v1/articles/' . $id . '/images', $cache);
  }

  public function getArticleVideos($id, $cache = true) {
    return $this->call($this->hailBaseURI . 'api/v1/articles/' . $id . '/videos', $cache);
  }


  public function import($cache = false) {
    $ptag = $this->getPrimaryPtag();

    $data = $this->getArticlesByPrivateTag($ptag, false);

    // TODO:
    // anything I can do with GUIDs?
    // if no posts at all, is wp_ids still an array?

    $hail_ids = array();

    $query_args = array(
      'posts_per_page' => -1,
      'post_type' => 'hail_article'
    );
    $query = new WP_Query($query_args);
    $wp_ids = wp_list_pluck($query->posts, 'hail_id', 'ID');

    foreach ($data as $article) {

      echo '<pre>';
      echo 'the hail API gave me an article with id: ' . $article['id'];
      echo '</pre>';

      $hail_ids[] = $article['id'];

      $ptags = [];
      foreach($article['private_tags'] as $ptag) {
        $ptags[] = $ptag['name'];
      }

      echo '<pre>';
      print_r($ptags);
      echo '</pre>';

      // see if an existing post exists
      $query_args = array(
        'posts_per_page' => -1,
        'post_type' => 'hail_article',
        'meta_query' => array(
          array(
            'key' => 'hail_id',
            'value' => $article['id'],
            'compare' => 'LIKE'
          )
        )
      );


      $args = array(
        'post_type'    => 'hail_article',
        'meta_key'     => 'hail_id',
        'meta_value'   => $article['id'],
        'meta_compare' => 'like'
      );
      $query = new WP_Query($args);

      $existing_id = false;
      $has_updated = false;
      $existing = $query->get_posts();
      if (count($existing) > 0) {
        // echo '<pre>';
        // print_r($existing);
        // echo '</pre>';
        $existing = $existing[0];
        $existing_id = $existing->ID;
        $then = Carbon::parse(get_post_meta($existing_id, 'updated_date', true));
        $now = Carbon::parse($article['updated_date']);
        $has_updated = !$then->eq($now);
      }



      if ($existing_id) {
        echo '<pre>';
        echo get_post_meta($existing_id, 'updated_date', true) . '<br />';
        echo $article['updated_date'] . '<br />';
        echo $has_updated ? 'has updated' : 'has not updated';
        echo '</pre>';
      }

      if ($existing_id && !$has_updated) {
        echo '<pre>continuing</pre>';
        continue;
      }

      $post = array(
        'post_type' => 'hail_article',
        'post_status' => 'publish',
        'post_title' => $article['title'],
        'post_content' => $article['lead'] . $article['body']
      );

      $hero_url = false;
      $hero_image = $article['hero_image'];
      if ($hero_image) {
        $hero_url = $hero_image['file_1000_url'];
      }

      if ($has_updated) {

        echo '<pre>updating existing post</pre>';
        update_post_meta($existing_id, 'lead', $article['lead']);
        update_post_meta($existing_id, 'body', $article['body']);
        update_post_meta($existing_id, 'date', $article['date']);
        update_post_meta($existing_id, 'updated_date', $article['updated_date']);

        // update the post_tags
        wp_set_object_terms($existing_id, $ptags, 'post_tag');

      } else {

        echo '<pre>creating new post</pre>';

        $id = wp_insert_post(add_magic_quotes($post));
        if ($id) {
          add_post_meta($id, 'hail_id', $article['id']);
          add_post_meta($id, 'lead', $article['lead']);
          add_post_meta($id, 'body', $article['body']);
          add_post_meta($id, 'date', $article['date']);
          add_post_meta($id, 'updated_date', $article['updated_date']);
          if ($hero_url) add_post_meta($id, 'hero_url', $hero_url);

          // add the Hail tags as post_tags
          wp_set_object_terms($id, $ptags, 'post_tag');
        } else {
          throw new Exception('Wordpress post for Hail article was not created.');
        }

      }

    } // end foreach

    //
    // echo '<pre>';
    // echo 'WP IDS';
    // print_r($wp_ids);
    // echo '</pre>';
    //
    // echo '<pre>';
    // echo 'HAIL IDS';
    // print_r($hail_ids);
    // echo '</pre>';
    //

    $to_delete = array_keys(array_diff($wp_ids, $hail_ids));


    foreach ($to_delete as $wp_id) {
      echo '<pre>';
      echo 'deleting ' . $wp_id;
      echo '</pre>';
      wp_delete_post($wp_id, true); // true for force delete (bypass trash)
    }


    // echo '<pre>';
    // echo 'DIFF';
    // print_r(array_diff($wp_ids, $hail_ids));
    // echo '</pre>';


    return;


  }

}
