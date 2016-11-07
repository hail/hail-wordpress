<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/hail/hail-wordpress
 * @since      1.0.0
 *
 * @package    Hail
 * @subpackage Hail/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Hail
 * @subpackage Hail/public
 * @author     Benjamin Dawson <ben@hail.to>
 */
class Hail_Public {

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

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->helper = Hail_Helper::getInstance();

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/hail-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hail-public.js', array( 'jquery' ), $this->version, false );

	}

	public function hail_shortcode($attrs) {

		// Default attributes
		$attrs = shortcode_atts( array(

			'hail_tag' 				=> false,

			// 'display_types'   => true,
			// 'display_tags'    => true,
			// 'display_content' => true,
			// 'display_author'  => false,
			// 'show_filter'     => false,
			// 'include_type'    => false,
			// 'include_tag'     => false,
			'display_hero'	  => false,
			'display_content' => true,
			'columns'         => 2,
			'showposts'       => -1,
			'order'           => 'asc',
			'orderby'         => 'date',
		), $attrs, 'hail_content' );

		// TODO: sanitization (copy from jetpack portfolio_shortcode function)
		// order and order by sanitization

		if ($attrs['hail_tag']) {
			$attrs['hail_tag'] = explode(',', str_replace(' ', '', $attrs['hail_tag']));
		}

		if ($attrs['display_hero'] && 'true' != $attrs['display_hero']) {
			$attrs['display_hero'] = false;
		}

		if ($attrs['display_content'] && 'true' != $attrs['display_content'] && 'full' != $attrs['display_content']) {
			$attrs['display_content'] = false;
		}

		$attrs['columns'] = absint($attrs['columns']);

		$attrs['showposts'] = intval($attrs['showposts']);

		if ($attrs['order']) {
			$attrs['order'] = urldecode($attrs['order']);
			$attrs['order'] = strtoupper($attrs['order']);
			if ($attrs['order'] != 'DESC') {
				$attrs['order'] = 'ASC';
			}
		}

		if ($attrs['orderby']) {
			$attrs['orderby'] = urldecode($attrs['orderby']);
			$attrs['orderby'] = strtolower($attrs['orderby']);
			$allowed_keys = array('author', 'date', 'title', 'rand');

			$parsed = array();
			foreach (explode(',', $attrs['orderby']) as $hail_index_number => $orderby) {
				if (!in_array($orderby, $allowed_keys)) {
					continue;
				}
				$parsed[] = $orderby;
			}

			if (empty($parsed)) {
				unset($attrs['orderby']);
			} else {
				$attrs['orderby'] = implode(' ', $parsed);
			}
		}

		// add custom styles for this shortcode
		// error_log('** ' . plugins_url() . ' **');
		// wp_enqueue_style('hail-shortcode-style', plugins_url(''))

		return $this->helper->shortcodeHTML($attrs);

	}


	/**
	 * Checks if the template is assigned to the page
	 */
	// public function view_project_template( $template ) {
	//
	// 	global $post;
	//
	// 	if (!$post || is_search()) return $template;
	//
	// 	if ($post->post_type == 'hail_article') {
	// 		// $plugin_template = plugin_dir_path(__FILE__) . 'templates/hail-test-template.php';
	// 		$plugin_dir = dirname(__FILE__);
	//
	// 		die($plugin_dir . '/templates/hail-test-template.php');
	//
	// 		// $plugin_template = plugin_dir_path(__FILE__) . 'templates/hail-test-template.php';
	//
	// 		return $plugin_template;
	// 	}
	//
	// 	// error_log(var_export($post, true));
	// 	//
	// 	// if (
	// 	// 	!isset(
	// 	// 		$this->templates[
	// 	// 			get_post_meta($post->ID, '_wp_page_template', true)
	// 	// 		]
	// 	// 	)
	// 	// ) {
	// 	// 	return $template;
	// 	// }
	// 	//
	// 	// $file = plugin_dir_path(__FILE__) . get_post_meta(
	// 	// 	$post->ID, '_wp_page_template', true
	// 	// );
	//
	// 	// Just to be safe, we check if the file exist first
	// 	// if (file_exists($file)) {
	// 	// 	return $file;
	// 	// } else {
	// 	// 	echo $file;
	// 	// }
	//
	// 	return $template;
	//
	// }

}
