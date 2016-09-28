<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
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
 * @author     Your Name <email@example.com>
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
		// add_options_page(
		// 	'Hail integration setup',
		// 	'Hail',
		// 	'manage_options',
		// 	$this->plugin_name,
		// 	array(
		// 		$this,
		// 		'display_plugin_setup_page'
		// 	)
		// );

		add_menu_page(
			'Hail Integration Setup',
			'Hail',
			'manage_options',
			$this->plugin_name,
			array(
				$this,
				'display_plugin_setup_page'
			),
			'dashicons-welcome-learn-more',
			61
		);
	}


	public function display_plugin_setup_page() {
    include_once( 'partials/hail-admin-display.php' );
	}

	public function options_update() {
		register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
	}

	// validate user input
	public function validate($input) {
    $valid = array();

		$valid['client_id'] = esc_attr($input['client_id']);
		$valid['client_secret'] = esc_attr($input['client_secret']);
    $valid['enable_redis'] = (isset($input['enable_redis']) && !empty($input['enable_redis'])) ? 1 : 0;
		$valid['primary_ptag'] = esc_attr($input['primary_ptag']);

    return $valid;
	}

	// create the custom taxonomy for Hail tags
	public function create_taxonomy() {

		$labels = array(
			'name'                       => _x( 'Hail Tags', 'taxonomy general name', 'textdomain' ),
			'singular_name'              => _x( 'Hail Tag', 'taxonomy singular name', 'textdomain' ),
			'search_items'               => __( 'Search Hail Tags', 'textdomain' ),
			'popular_items'              => __( 'Popular Hail Tags', 'textdomain' ),
			'all_items'                  => __( 'All Hail Tags', 'textdomain' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Hail Tag', 'textdomain' ),
			'update_item'                => __( 'Update Hail Tag', 'textdomain' ),
			'add_new_item'               => __( 'Add New Hail Tag', 'textdomain' ),
			'new_item_name'              => __( 'New Hail Tag Name', 'textdomain' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'textdomain' ),
			'add_or_remove_items'        => __( 'Add or remove Hail Tags', 'textdomain' ),
			'choose_from_most_used'      => __( 'Choose from the most used Hail Tags', 'textdomain' ),
			'not_found'                  => __( 'No Hail Tags found.', 'textdomain' ),
			'menu_name'                  => __( 'Hail Tags', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array('slug' => 'hail_tag'),
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
					'title'
				),
				'taxonomies' => array(
					'hail_tag'
				),
				'has_archive' => false,
				'can_export' => false
			)
		);

	}


	public function hail_import() {

		$this->helper->import();

	}


}
