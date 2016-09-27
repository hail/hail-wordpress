<?php

/**
* Provide a admin area view for the plugin
*
* This file is used to markup the admin-facing aspects of the plugin.
*
* @link       http://example.com
* @since      1.0.0
*
* @package    Hail
* @subpackage Hail/admin/partials
*/

$test_result = null;
if (isset($_GET['action'])) {
  if ($_GET['action'] == 'import') {
    $json = $this->helper->import();

  }
}

?>

<div class="wrap">

  <h2><?php echo esc_html(get_admin_page_title()); ?></h2>

  <?php

  $predis_test = $this->helper->predis ? true : false;

  $test_result = null;
  // TODO: also account for manual testing?
  // manual testing confirms existance of private tag?

  try {

    $json = $this->helper->test();
    if ($json['id']) {
      $test_result = true;
    } else {
      $test_result = false;
    }

  } catch (Exception $e) {

    // set the test result to the error message?
    $test_result = false;

  }

  if (!is_null($test_result)) {
    if ($test_result) {
      ?>
      <!-- <div class="updated"><p>cred test success</p></div> -->
      <?php
    } else {
      ?>
      <!-- <div class="error"><p>cred test failed</p></div> -->
      <?php
    }
  }


  // $test_result = null;
  // if (isset($_GET['action'])) {
  //   if ($_GET['action'] == 'test') {
  //     $json = $this->helper->test();
  //
  //     if ($json['id']) {
  //       $test_result = true;
  //     } else {
  //       $test_result = false;
  //     }
  //   }
  // }
  //
  // if (!is_null($test_result)) {
  //   if ($test_result) {
  //
  //     <div class="updated"><p>Test successful</p></div>
  //
  //   } else {
  //
  //     <div class="error"><p>Test failed, try reauthorizing</p></div>
  //
  //   }
  // }

  ?>

  <form method="post" name="setup-options" action="options.php">

    <?php

      settings_fields($this->plugin_name);
      do_settings_sections($this->plugin_name);

      if (isset($_GET['code']) && !empty($_GET['code'])) {
        // get the access token
        $access_token = $this->helper->getAccessToken('authorization_code', [
          'code' => $_GET['code']
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

        $this->helper->toAdminUrlDefault();

      }

      $client_id = $this->helper->getClientId();
      $client_secret = $this->helper->getClientSecret();
      $redis_enabled = $this->helper->getRedisEnabled();
      $primary_ptag = $this->helper->getPrimaryPtag();

      $authorisation_url = $this->helper->getAuthorizationUrl();

      $hail_test_nonce = wp_create_nonce('hail-test');
      $hail_test_url = add_query_arg(
        array(
          'action' => 'test',
          'nonce' => $hail_test_nonce
        ),
        admin_url('admin.php?page=' . $this->plugin_name)
      );

      $hail_import_nonce = wp_create_nonce('hail-test');
      $hail_import_url = add_query_arg(
        array(
          'action' => 'import',
          'nonce' => $hail_import_nonce
        )
      );

    ?>

    <!-- OAUTH -->
    <fieldset>
      <legend class="screen-reader-text"><span>Client ID</span></legend>
      <label for="<?php echo $this->plugin_name;?>-client_id">
          <input type="text" class="<?php echo $this->plugin_name;?>-client_id" id="<?php echo $this->plugin_name;?>-client_id" name="<?php echo $this->plugin_name;?>[client_id]"  value="<?php echo $client_id;?>"  />
          <span>Client ID</span>
      </label>
    </fieldset>

    <fieldset>
      <legend class="screen-reader-text"><span>Client Secret</span></legend>
      <label for="<?php echo $this->plugin_name;?>-client_secret">
          <input type="text" class="<?php echo $this->plugin_name;?>-client_secret" id="<?php echo $this->plugin_name;?>-client_secret" name="<?php echo $this->plugin_name;?>[client_secret]"  value="<?php echo $client_secret;?>"  />
          <span>Client Secret</span>
      </label>
    </fieldset>

    <!-- Tags and stuff -->
    <fieldset>
      <legend class="screen-reader-text"><span>Primary Private Tag ID</span></legend>
      <label for="<?php echo $this->plugin_name;?>-primary_ptag">
        <input type="text" class="<?php echo $this->plugin_name;?>-primary_ptag" id="<?php echo $this->plugin_name;?>-primary_ptag" name="<?php echo $this->plugin_name;?>[primary_ptag]" value="<?php echo $primary_ptag;?>" />
        <span>Primary Private Tag ID</span>
      </label>
    </fieldset>

    <!-- REDIS -->
    <?php
    if ($predis_test) {
    ?>
    <fieldset>
      <legend class="screen-reader-text"><span>Enable Redis caching</span></legend>
      <label for="<?php echo $this->plugin_name; ?>-enable_redis">
        <input type="checkbox" id="<?php echo $this->plugin_name; ?>-redis_enabled" name="<?php echo $this->plugin_name; ?>[redis_enabled]" value="1" <?php checked($redis_enabled, 1); ?> />
        <span><?php esc_attr_e('Enable Redis caching', $this->plugin_name); ?></span>
      </label>
    </fieldset>
    <?php
    }
    ?>

    <?php
    if ($client_id && $client_secret) { ?>
      <a class="button-primary" href="<?php echo $authorisation_url; ?>">Authorise</a>
    <?php } ?>

    <?php
    if ($test_result && $primary_ptag) { ?>
      <a class="button-primary" href="<?php echo $hail_test_url; ?>">Test</a>
      <a class="button-primary" href="<?php echo $hail_import_url; ?>">Import</a>
    <?php } ?>

    <?php submit_button('Save all changes', 'primary', 'submit', TRUE); ?>

  </form>

</div>
