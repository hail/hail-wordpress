<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/hail/hail-wordpress
 * @since      1.0.0
 *
 * @package    Hail
 * @subpackage Hail/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Hail
 * @subpackage Hail/admin
 * @author     Benjamin Dawson <ben@hail.to>
 */
class Hail_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public $helper;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// $this->helper = new Hail_Helper($plugin_name);
		$this->helper = Hail_Helper::getInstance();

		// mustache for html templating
		$this->mustache = new \Mustache_Engine(
			array(
				'loader' => new \Mustache_Loader_FilesystemLoader(dirname(__DIR__) . '/views/admin'),
				'cache' => dirname(__DIR__) . '/views/admin/cache'
			)
		);


		// $this->templates = array(
		// 	'templates/hail-test-template.php' => 'Hail Test Template'
		// );

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/hail-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hail-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function add_plugin_admin_menu() {
		add_options_page(
			'Hail integration setup',
			'Hail',
			'manage_options',
			$this->plugin_name,
			array(
				$this,
				'display_plugin_setup_page'
			)
		);
	}


	public function display_plugin_setup_page() {

		// are we completing the oauth flow or doing an action?
		// we don't do an action and complete the oauth flow at the same time
		if (isset($_GET['code']) && !empty($_GET['code'])) {
			// this then redirects to the default wordpress hail section url
			$this->helper->completeOAuthFlow($_GET['code']);
			return;
		}

		$import_results = false;

		if (isset($_GET['action'])) {
			// what actions do we have
			// import => run import task
			// code => complete oauth flow

			if ($_GET['action'] == 'import') {
				list($import_new, $import_changed, $import_deleted) = $this->helper->import();
				$import_results = 'New: ' . $import_new . ', Changed: ' . $import_changed . ', Deleted: ' . $import_deleted;
			}
		}

		$client_id = $this->helper->getConfigClientId();
		$client_secret = $this->helper->getConfigClientSecret();
		// $redis_enabled = $this->helper->getConfigRedisEnabled();
		$primary_ptag = $this->helper->getConfigPrimaryPtag();
		$authorization_url = $this->helper->getAuthorizationUrl();
		$user_id = get_option('hail-user_id');
		$organisation_id = get_option('hail-organisation_id');

		// do I need nonce?
		// can I test that the nonce does what it's supposed to do?
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


		$test_class = null;
		$test_text = null;


		$has_authorised = get_option('hail-access_token') ? true : false;
		$importable = false;

		// if it looks like we've authorised before
		if ($has_authorised) {

			// tests and ptag fetching
			if ($client_id && $client_secret) {

				if (!$primary_ptag) {
					if ($this->helper->testMe()) {
						$test_class = 'updated';
						$test_text = 'Credentials are correct';
					} else {
						$test_class = 'error';
						$test_text = 'The provided credentials don\'t appear to be correct';
					}
				} else {
					if ($this->helper->testPtag($primary_ptag)) {
						$test_class = 'updated';
						$test_text = 'Credentials are correct, and Private Tag is accessible';
						$importable = true;
					} else {
						$test_class = 'error';
						$test_text = 'The specified Private Tag could not be accessed using the given credentials';
					}
				}

			} else {

				// if we've authorised in the past but the client_id or client_secret
				// aren't set then remove access tokens etc and both the client_id
				// and client_secret

				// TODO: perhaps use helper functions for setting / updating and deleting
				// these options?
				delete_option('hail-access_token');
				delete_option('hail-refresh_token');
				delete_option('hail-expires');

				delete_option($this->plugin_name);

				$client_id = null;
				$client_secret = null;
				$primary_ptag = null;

				$has_authorised = false;

			}

		}

		$authorisable = $client_id && $client_secret;
		$redirect_uri = admin_url('options-general.php?page=' . $this->plugin_name);

		$help = false;

		// perhaps test the error here, and skip the whole info block (or
		// provide an alternative error-like one)

		// help text depending on status (doesn't account for errors)
		if (!$authorisable) {

			$help = '<p>To get started, <a href="https://hail.to/app/user/applications">register an OAuth client in Hail</a>. You\'ll need to add an <strong>OAuth redirect URI</strong>, which should be set to <code>' . $redirect_uri . '</code></p>' .
			  			'<p>Once that\'s done, enter in your <strong>Client ID</strong> and <strong>Client Secret</strong> and hit the save button below.</p>';

		} else if ($authorisable && !$has_authorised) {

			$help = '<p>Looking good so far. Now click the "Authorise" button below to create the connection to Hail.</p>';

		} else if (!$primary_ptag) {
			// you've authorised but haven't set a ptag

			$help = '<p>Great work! Now you need to add a primary Private Tag ID for pulling in a subset of your Hail Content. Unless you\'re a developer, you probably want to <a href="mailto:support@hail.to">contact Hail support</a> and ask for some help.</p>';

		} else {

			$help = '<p>Fantastic! It looks like you\'ve done everything correctly. You can manually import your Hail content using the "Import" button below, but it will be imported on a schedule anyway.</p>';

		}

		$html = $this->mustache->render(
			'settings',
			array(

				'plugin_name' => $this->plugin_name,
				'title' => get_admin_page_title(),

				'settings_nonce' => wp_nonce_field($this->plugin_name . '-options'),

				'redirect_uri' => $redirect_uri,

				// tests
				'test_class' => $test_class,
				'test_text' => $test_text,

				// help copy
				'help' => $help,

				// conditional based on current progress
				'show_ptag' => $has_authorised,

				// config
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'primary_ptag' => $primary_ptag,

				'authorisable' => $authorisable,
				'importable' => $importable,

				'authorization_url' => $authorization_url,
				'hail_import_url' => $hail_import_url,

				'submit_button' => get_submit_button('Save all changes', 'primary', 'submit', false),

				'import_results' => $import_results,
			)
		);

		echo $html;

		// can I use straight php / wp functions from within the mustache templates?
		// e.g. esc_html(), get_admin_page_title()

		// a way of testing the current state of the configuration
		// helper->test

		// a way of displaying the results of the test if necessary

		// settings_fields ad do_settings_sections. do they work from mustache? how so?

		// test for $_GET['code'] for oauth flow callback

		// display form with current settings

		// test button with nonce?

		// client id
		// client secret
		// primary private tag id
		// redis options

		// buttons and actions
		// authorise / reauthorise
		// test
		// manual import
		// proper submit button

	}

	private function test() {
		// perform some tests and return a result

		$test_result = true;

		return $test_result;
	}

	public function options_update() {
		register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
	}

	// validate user input
	public function validate($input) {
    $valid = array();

		$valid['client_id'] = esc_attr($input['client_id']);
		$valid['client_secret'] = esc_attr($input['client_secret']);
    // $valid['enable_redis'] = (isset($input['enable_redis']) && !empty($input['enable_redis'])) ? 1 : 0;
		$valid['primary_ptag'] = esc_attr($input['primary_ptag']);

    return $valid;
	}

	// create the custom taxonomy for Hail tags
	public function create_taxonomy() {

		$labels = array(
			'name' => _x( 'Hail Tags', 'taxonomy general name', 'textdomain' ),
			'singular_name' => _x( 'Hail Tag', 'taxonomy singular name', 'textdomain' ),
			'search_items' => __( 'Search Hail Tags', 'textdomain' ),
			'popular_items' => __( 'Popular Hail Tags', 'textdomain' ),
			'all_items' => __( 'All Hail Tags', 'textdomain' ),
			'parent_item' => null,
			'parent_item_colon' => null,
			'edit_item' => __( 'Edit Hail Tag', 'textdomain' ),
			'update_item' => __( 'Update Hail Tag', 'textdomain' ),
			'add_new_item' => __( 'Add New Hail Tag', 'textdomain' ),
			'new_item_name' => __( 'New Hail Tag Name', 'textdomain' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'textdomain' ),
			'add_or_remove_items' => __( 'Add or remove Hail Tags', 'textdomain' ),
			'choose_from_most_used' => __( 'Choose from the most used Hail Tags', 'textdomain' ),
			'not_found' => __( 'No Hail Tags found.', 'textdomain' ),
			'menu_name' => __( 'Hail Tags', 'textdomain' ),
		);

		$args = array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_admin_column' => true,
			// 'query_var' => true, // defaults to taxonomy name
			'rewrite' => array('slug' => 'hail_tag'),
			'show_ui' => false,
			'public' => false
		);

		register_taxonomy(
			'hail_tag',
			array('hail_article'),
			$args
		);

	}


	// create the custom post type for storing Hail articles
	public function create_post_type() {

		// TODO:
		// https://codex.wordpress.org/Function_Reference/register_post_type
		// specify capabilities
		// specify post formats? https://codex.wordpress.org/Post_Formats
		// taxonomies?
		//

		$capability_type = 'hail_article';

		// some of these are defaults
		register_post_type(
			'hail_article',
			array(
				'labels' => array(
					'name' => __('Hail Articles'),
					'singular_name' => __('Hail Article')
				),
				'public' => true,
				'show_ui' => true,
				'show_in_nav_menus' => false,
				'hierarchical' => false,
				'supports' => array(
					// keep this here in case we want to change the title
					'title'
				),
				'taxonomies' => array(
					'hail_tag'
				),
				'has_archive' => false,
				'can_export' => false,
				'menu_icon' => 'dashicons-welcome-learn-more',
				'map_meta_cap' => true,
				'capability_type' => array($capability_type, $capability_type . 's'),

				'capabilities' => array(
					'edit_post' => 'edit_' . $capability_type,
					'read_post' => 'read_' . $capability_type,
					'delete_post' => 'delete_' . $capability_type,

					'edit_posts' => 'edit_' . $capability_type . 's',
					'edit_others_posts' => 'edit_others_' . $capability_type . 's',
					'publish_posts' => 'publish_' . $capability_type . 's',
					'read_private_posts' => 'read_private_' . $capability_type . 's',

					'read' => 'read',
					'delete_posts' => 'delete_' . $capability_type . 's',
					'delete_private_posts' => 'delete_private_' . $capability_type . 's',
					'delete_published_posts' => 'delete_published_' . $capability_type . 's',
					'delete_others_posts' => 'delete_others_' . $capability_type . 's',
					'edit_private_posts' => 'edit_private_' . $capability_type. 's',
					'edit_published_posts' => 'edit_published_' . $capability_type. 's',
					// 'create_posts' => 'edit_' . $capability_type . 's',
					'create_posts' => false
				),
			)
		);

	}

	public function configure_cpt_roles() {
		$role = get_role('administrator');
		$role->add_cap('read_hail_article');
		$role->add_cap('read_hail_articles');
		$role->add_cap('edit_hail_article');
		$role->add_cap('edit_hail_articles');
		$role->add_cap('edit_published_hail_articles');
		$role->add_cap('edit_others_hail_articles');
	}

	public function hide_meta_boxes() {

		// remove_meta_box('submitdiv', 'hail_article', 'side');
		// remove_meta_box('slugdiv', 'hail_article', 'side');
		remove_meta_box('submitdiv', 'hail_article', 'normal');

		// hiding the slug div doesn't seem to work
		// remove_meta_box('slugdiv', 'hail_article', 'normal');
		// remove_meta_box('slugdiv', 'hail_article', 'side');
		// remove_meta_box('slugdiv', 'hail_article', 'advanced');

	}

	public function remove_row_actions($actions, $post) {

		// error_log('*******************');
		// error_log(var_export($actions, true));
		// error_log('-------------------');
		//
		// error_log('*******************');
		// error_log(var_export($post, true));
		// error_log('-------------------');

	  global $current_screen;

    if ($current_screen->post_type != 'hail_article' ) return $actions;
    unset($actions['inline hide-if-no-js']);

    return $actions;
	}

	function remove_bulk_actions($actions) {
		unset($actions['edit']);
		return $actions;
	}

	// public interface
	public function hail_import() {

		$this->helper->import();

	}


}
