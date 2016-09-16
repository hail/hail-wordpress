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

		$this->helper = new Hail_Helper($plugin_name);

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

	// public function add_action_links( $links ) {
	//
  //   /*
  //   *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
  //   */
	//
	// 	$settings_link = array(
	// 		'<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __('Settings', $this->plugin_name) . '</a>',
	// 	);
	// 	return array_merge(  $settings_link, $links );
	//
	// }

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

    return $valid;
 }

}
