<?php

/**
 * Define the Hail helper functions for OAuth and accessing the Hail API
 *
 * @link          https://github.com/hail/hail-wordpress
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
  private $hailBaseURI;
  // private $clientID;
  // private $clientSecret;
  private $config;

  private static $instance;

  function __construct($plugin_name) {
    // for testing only
    // $this->dev_mode = (WP_ENV == 'development' ? true : false);
    $this->dev_mode = false;
    $this->hailBaseURI = ($this->dev_mode ? 'https://dev.hail.to/' : 'https://hail.to/');

    $this->plugin_name = $plugin_name;

    $this->config = get_option($this->plugin_name) ?: array();

    $this->guzzle = new GuzzleHttp\Client();
    // $this->guzzle->setDefaultHeaders(
    //   array(
    //
    //   )
    // );

    // wp_cache_set('hail-test', 'cached-value');
    //
    // error_log('%%%%' . wp_cache_get('hail-test') . '%%%%');

  }

  private function initProvider() {
    if (!array_key_exists('client_id', $this->config) || !array_key_exists('client_secret', $this->config)) {
      $this->provider = false;
      return;
    }

    $this->provider = new League\OAuth2\Client\Provider\HailProvider([
      'clientId'                => $this->config['client_id'],    // The client ID assigned to you by the provider
      'clientSecret'            => $this->config['client_secret'],   // The client password assigned to you by the provider
      'redirectUri'             => admin_url('options-general.php?page=' . $this->plugin_name),
      'urlAuthorize'            => $this->hailBaseURI . 'oauth/authorise',
      'urlAccessToken'          => $this->hailBaseURI . 'api/v1/oauth/access_token',
      'urlResourceOwnerDetails' => $this->hailBaseURI . 'api/v1/me',
      'devMode'                 => $this->dev_mode
    ]);
  }

  public static function getInstance() {
    if (!isset(self::$instance)) {
      self::$instance = new self('hail');
    }

    return self::$instance;
  }

  private function log($thing) {
    if ($this->dev_mode) {
      error_log("***\n" . var_export($thing, true));
    }
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

  public function getConfigClientID() {
    return array_key_exists('client_id', $this->config) ? $this->config['client_id'] : '';
  }

  public function getConfigClientSecret() {
    return array_key_exists('client_secret', $this->config) ? $this->config['client_secret'] : '';
  }

  // public function getConfigRedisEnabled() {
  //   return array_key_exists('redis_enabled', $this->config) ? $this->config['redis_enabled'] : 0;
  // }

  public function getConfigPrimaryPtag() {
    return array_key_exists('primary_ptag', $this->config) ? $this->config['primary_ptag'] : '';
  }

  public function getAuthorizationUrl() {
    if (!$this->provider) {
      $this->initProvider();
    }
    return $this->provider ? $this->provider->getAuthorizationUrl() : null;
  }

  public function getAccessToken($type, $data) {
    if (!$this->provider) {
      $this->initProvider();
    }
    return $this->provider ? $this->provider->getAccessToken($type, $data) : null;
  }



  // always a GET call
  private function call($url, $cache = true) {
    $url = $this->hailBaseURI . $url;

    // $this->log($url);

    if (!$this->provider) {
      $this->initProvider();
    }

    if (!$this->provider) {
      throw new Exception('Not enough data was configured to instantiate the provider');
    }

    // create an hash of the URL being requested
    // look up the hash in the cache
    // if matched then return cached results
    // if not matched then proceed to make request and then store result against hash

    if ($cache) {
      $hash = 'hail' . sha1($url);
      // $this->log($hash);
      $cached_result_body = get_transient($hash);

      // $this->log('cached result body: ' . $cached_result_body);

      if ($cached_result_body) {
        $this->log('cache hit');
        return json_decode($cached_result_body, true);
      } else {
        $this->log('no cache hit');
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
      // hail user will be the same

    }

    $headers = array(
      'Authorization' => 'Bearer ' . $token->getToken()
    );

    $request = new Request('GET', $url, $headers);

    $result_body = $this->guzzle->send($request)->getBody();

    // error_log('&&&&');
    // error_log($result_body);
    // error_log('&&&&');

    $json = json_decode($result_body, true);

    if ($cache) {
      $this->log('setting cache: ' . $hash);
      set_transient($hash, (string) $result_body, 180);
    }

    return $json;
  }

  public function completeOAuthFlow($code) {

    $access_token = $this->getAccessToken('authorization_code', [
      'code' => $code
    ]);

    // TODO: combine these into the same option set?

    // update the tokens if necessary
    if (get_option('hail-access_token') !== false) {
      update_option('hail-access_token', $access_token->getToken());
    } else {
      add_option('hail-access_token', $access_token->getToken());
    }

    if (get_option('hail-refresh_token') !== false) {
      update_option('hail-refresh_token', $access_token->getRefreshToken());
    } else {
      add_option('hail-refresh_token', $access_token->getRefreshToken());
    }

    if (get_option('hail-expires') !== false) {
      update_option('hail-expires', $access_token->getExpires());
    } else {
      add_option('hail-expires', $access_token->getExpires());
    }


    // make a request to /me and store the user id
    try {
      $result = $this->call('api/v1/me', false);
      $user_id = $result['id'];
      if (get_option('hail-user_id') !== false) {
        update_option('hail-user_id', $user_id);
      } else {
        add_option('hail-user_id', $user_id);
      }
    } catch (Exception $e) {
      // fatal error
      // TODO: do something interesting
    }

    $this->toAdminUrlDefault();

  }

  public function testMe() {
    try {
      $result = $this->call('api/v1/me', false);
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  public function testPtag($id) {
    try {
      $result = $this->call('api/v1/private-tags/' . $id, false);
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  // public function getPtags() {
  //   return $this->call('api/v1/')
  // }

  // TODO: need the user id to fetch my organisations.
  // potentially store the user id against the helper assuming that me has been fetched
  // me should probably be fetched as part of the oauth flow completion

  // once we have the user id we can request the organisations
  public function getOrganisations() {
    // TODO: I really need to consolidate the config. stupid wordpress
    return $this->call('api/v1/users/' . get_option('hail-user_id') . '/organisations');
  }

  // you should only be connecting to one organisation (one ptag is used), so that
  // organisation id can be stored as config

  // once we have the organisations we can fetch the organisatinso private tags
  public function getPrivateTags() {
    return $this->call();
  }

  public function getArticle($id) {
    return $this->call('api/v1/articles/' . $id);
  }

  public function getImage($id) {
    if (!$id) return false;
    return $this->call('api/v1/images/' . $id);
  }

  public function getVideo($id) {
    if (!$id) return false;
    return $this->call('api/v1/videos/' . $id);
  }

  public function getArticlesByPrivateTag($id, $cache = true) {
    // https://dev.hail.to/api/v1/private-tags/58kHKvj/articles?limit=50&order=date%7Cdesc&offset=0
    return $this->call('api/v1/private-tags/' . $id . '/articles?status=published', $cache);
  }

  public function getArticleImages($id, $cache = true) {
    return $this->call('api/v1/articles/' . $id . '/images', $cache);
  }

  public function getArticleVideos($id, $cache = true) {
    return $this->call('api/v1/articles/' . $id . '/videos', $cache);
  }

  public function getArticleAttachments($id, $cache = true) {
    return $this->call('api/v1/articles/' . $id . '/attachments', $cache);
  }

  public function getPublicationsByPrivateTag($id, $limit = false, $cache = true) {
    $url = 'api/v1/private-tags/' . $id . '/publications?status=published';
    if ($limit) {
      $url .= '&limit=' . $limit;
    }
    $url .= '&order=date%7Cdesc';
    return $this->call($url, $cache);
  }

  public function import($cache = false) {
    $ptag = $this->getConfigPrimaryPtag();

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

    $count_new = 0;
    $count_changed = 0;
    $count_deleted = 0;

    foreach ($data as $article) {

      // echo '<pre>';
      // echo 'the hail API gave me an article with id: ' . $article['id'];
      // echo '</pre>';

      $hail_ids[] = $article['id'];

      $ptags = [];
      foreach($article['tags'] as $ptag) {
        $ptags[] = $ptag['name'];
      }

      // echo '<pre>';
      // print_r($ptags);
      // echo '</pre>';

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



      // if ($existing_id) {
        // echo '<pre>';
        // echo get_post_meta($existing_id, 'updated_date', true) . '<br />';
        // echo $article['updated_date'] . '<br />';
        // echo $has_updated ? 'has updated' : 'has not updated';
        // echo '</pre>';
      // }


      // don't do anything if the article already exists in WP and hasn't been updated in Hail
      if ($existing_id && !$has_updated) {
        // echo '<pre>continuing</pre>';
        continue;
      }

      $post = array(
        'post_type' => 'hail_article',
        'post_status' => 'publish',
        'post_title' => $article['title'],
        'post_content' => $article['lead'] . $article['body']
      );

      $hero_id = false;
      $hero_type = null;
      $hero_image = $article['hero_image'];
      $hero_video = $article['hero_video'];
      if ($hero_image) {
        $hero_id = $hero_image['id'];
        $hero_type = 'image';
      } else if ($hero_video) {
        $hero_id = $hero_video['id'];
        $hero_type = 'video';
      }

      if ($has_updated) {

        // echo '<pre>updating existing post</pre>';
        update_post_meta($existing_id, 'lead', $article['lead']);
        update_post_meta($existing_id, 'body', $article['body']);
        update_post_meta($existing_id, 'date', $article['date']);
        update_post_meta($existing_id, 'updated_date', $article['updated_date']);
        update_post_meta($existing_id, 'author', $article['author']);
        if ($hero_id) {
          update_post_meta($existing_id, 'hero_id', $hero_id);
          update_post_meta($existing_id, 'hero_type', $hero_type);
          if ($hero_type == 'image') {
            update_post_meta($existing_id, 'hero_url_1000', $hero_image['file_1000_url']);
            update_post_meta($existing_id, 'hero_url_500', $hero_image['file_500_url']);
            update_post_meta($existing_id, 'hero_url_500_square', $hero_image['file_500_square_url']);
          } else if ($hero_type == 'video') {
            update_post_meta($existing_id, 'hero_url_1000', $hero_video['preview']['file_1000_url']);
            update_post_meta($existing_id, 'hero_url_500', $hero_video['preview']['file_500_url']);
            update_post_meta($existing_id, 'hero_url_500_square', $hero_video['preview']['file_500_square_url']);
            update_post_meta($existing_id, 'hero_video_service', $hero_video['service']);
            update_post_meta($existing_id, 'hero_video_data', $hero_video['service_data']);
          }
        } else {
          delete_post_meta($existing_id, 'hero_id');
          delete_post_meta($existing_id, 'hero_type');
          delete_post_meta($existing_id, 'hero_url_1000');
          delete_post_meta($existing_id, 'hero_url_500');
          delete_post_meta($existing_id, 'hero_url_500_square');
          // is there any harm in deleting something that doesn't exist?
          delete_post_meta($existing_id, 'hero_video_service');
          delete_post_meta($existing_id, 'hero_video_data');
        }


        $post_update = array(
          'ID' => $existing_id,
          'post_content' => $article['lead'] . $article['body']
        );
        // if the article title has changed then update the actual post title
        // (not the meta data)
        if (strcmp($article['title'], get_the_title($existing_id)) !== 0) {

          // also need to update the actual post content as it's displayed using
          // the_content() in the shortcode HTML generator
          $post_update['post_title'] = $article['title'];

        }

        wp_update_post($post_update);

        // update the post_tags
        wp_set_object_terms($existing_id, $ptags, 'hail_tag');

        $count_changed++;


      } else {

        // echo '<pre>creating new post</pre>';

        $id = wp_insert_post(add_magic_quotes($post));
        if ($id) {
          add_post_meta($id, 'hail_id', $article['id']);
          add_post_meta($id, 'lead', $article['lead']);
          add_post_meta($id, 'body', $article['body']);
          add_post_meta($id, 'date', $article['date']);
          add_post_meta($id, 'updated_date', $article['updated_date']);
          add_post_meta($id, 'author', $article['author']);
          if ($hero_id) {
            // only store the image id as image data may change independently
            // of the article, so we'll fetch it (with optional cache) each time
            add_post_meta($id, 'hero_id', $hero_id);
            add_post_meta($id, 'hero_type', $hero_type);

            if ($hero_type == 'image') {
              add_post_meta($id, 'hero_url_1000', $hero_image['file_1000_url']);
              add_post_meta($id, 'hero_url_500', $hero_image['file_500_url']);
              add_post_meta($id, 'hero_url_500_square', $hero_image['file_500_square_url']);
            } else if ($hero_type == 'video') {
              add_post_meta($id, 'hero_url_1000', $hero_video['preview']['file_1000_url']);
              add_post_meta($id, 'hero_url_500', $hero_video['preview']['file_500_url']);
              add_post_meta($id, 'hero_url_500_square', $hero_video['preview']['file_500_square_url']);
              add_post_meta($id, 'hero_video_service', $hero_video['service']);
              add_post_meta($id, 'hero_video_data', $hero_video['service_data']);
            }

          }

          // add the Hail tags as post_tags
          wp_set_object_terms($id, $ptags, 'hail_tag');
          $count_new++;
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
      // echo '<pre>';
      // echo 'deleting ' . $wp_id;
      // echo '</pre>';
      wp_delete_post($wp_id, true); // true for force delete (bypass trash)
      $count_deleted++;
    }


    // echo '<pre>';
    // echo 'DIFF';
    // print_r(array_diff($wp_ids, $hail_ids));
    // echo '</pre>';

    return array(
      $count_new, $count_changed, $count_deleted
    );

  }

  public function deleteContent() {

    $query_args = array(
      'posts_per_page' => -1,
      'post_type' => 'hail_article'
    );

    $query = new WP_Query($query_args);

    $wp_ids = wp_list_pluck($query->posts, 'ID');

    $count_deleted = 0;

    foreach ($wp_ids as $wp_id) {
      wp_delete_post($wp_id, true);
      $count_deleted++;
    }

    return $count_deleted;

  }

  public function deleteSettings() {

    delete_option('hail-user_id');
    delete_option('hail-organisation_id');

    delete_option('hail-access_token');
    delete_option('hail-refresh_token');
    delete_option('hail-expires');

    delete_option('hail');

    // reset the config object in case it's used while this object is still alive
    $this->config = array();

  }

  public static function shortcodeQuery($attrs) {

    $default = array(
      'order' => $attrs['order'],
      'orderby' => $attrs['orderby'],
      'posts_per_page' => $attrs['showposts'],
    );

    if ($attrs['orderby'] == 'date') {
      // $default['orderby'] = 'meta_value_num';
      $default['meta_key'] = 'date';
      $default['meta_type'] = 'DATE';
    }

    $args = wp_parse_args($attrs, $default);
    $args['post_type'] = 'hail_article';

    if ($attrs['hail_tag'] != false) {
      $args['tax_query'] = array();

      array_push($args['tax_query'], array(
        'taxonomy' => 'hail_tag',
        'field'    => 'slug',
        'terms'    => $attrs['hail_tag']
      ));
    }

    $query = new WP_Query($args);

    return $query;

  }

  public function shortcodeHTML($attrs) {

    $query = self::shortcodeQuery($attrs);
    $hail_index_number = 0;

    // output buffering?
    ob_start();

    if ($query->have_posts()) {

      ?>
      <div class="hail-shortcode column-<?php echo esc_attr($attrs['columns']); ?>">
      <?php

      while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $hero_id = get_post_meta($post_id, 'hero_id', true);
        $hero_type = get_post_meta($post_id, 'hero_type', true);

        $hero_image = $hero_video = null;
        if ($hero_type == 'image') {
          $hero_image = $this->getImage($hero_id);
        } else if ($hero_type == 'video') {
          $hero_video = $this->getVideo($hero_id);
        }
        ?>

        <div class="hail-entry <?php echo esc_attr( self::get_article_class( $hail_index_number, $attrs['columns'] ) ); ?>">
          <header class="hail-entry-header">
            <?php
            if ($attrs['display_hero']) {

              if ($hero_type == 'image') {
                $hero_preview = $hero_image['file_500_square_url'];
              } else if ($hero_type == 'video') {
                $hero_preview = $hero_video['preview']['file_500_square_url'];
              }
              // self::get_hero_image($post_id);
              if ($hero_image || $hero_video) {
                echo '<a class="hail-featured-image" href="' . esc_url(get_permalink($post_id)) . '">' . '<img src="' . $hero_preview . '"></a>';
              }
            }
            ?>

            <h2 class="hail-entry-title"><a href="<?php echo esc_url(get_permalink()); ?>" title="<?php echo esc_attr(the_title_attribute()); ?>"><?php the_title(); ?></a></h2>

          </header>

          <?php
          if ($attrs['display_content'] !== false) {
            if ($attrs['display_content'] === 'full') {
              ?>
              <div class="hail-entry-content"><?php the_content(); ?></div>
              <?php
            } else {
              ?>
              <div class="hail-entry-content"><?php the_excerpt(); ?></div>
              <?php
            }
          }
          ?>

        </div><!-- /.hail-entry -->
        <?php $hail_index_number++;
      } // end of while loop

      wp_reset_postdata();
      ?>
      </div><!-- /.hail-shortcode -->
    <?php
    } else { ?>
      <p><em>There were no entries found</em></p>
    <?php
    }

    $html = ob_get_clean();

    // if there's a hail shortcode in the HTML then remove it
    if (has_shortcode($html, 'hail_content')) {
      remove_shortcode('hail_content');
    }

    return $html;

  }

  private static function get_article_class($hail_index_number, $columns) {

    $class = array();

    $class[] = 'hail-entry-column-' . $columns;

    if ($columns > 1) {
      if (($hail_index_number % 2) == 0) {
        $class[] = 'hail-entry-mobile-first-item-row';
      } else {
        $class[] = 'hail-entry-mobile-last-item-row';
      }
    }

    if (($hail_index_number % $columns) == 0) {
      $class[] = 'hail-entry-first-item-row';
    } elseif (($hail_index_number % $columns) == ($columns - 1)) {
      $class[] = 'hail-entry-last-item-row';
    }

    return implode(' ', $class);

  }

  // public helper functions
  public static function getCarbonDate($post_id) {
    return Carbon::parse(get_post_meta($post_id, 'date', true));
  }

  // private static function get_hero_image($post_id) {
  //   $url = get_post_meta($post_id, 'hero_url', true);
  //
  //   if ($url) {
  //     return '<a class="hail-featured-image" href="' . esc_url(get_permalink($post_id)) . '">' . '<img src="' . $url . '"></a>';
  //   }
  // }

}
