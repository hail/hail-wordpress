<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/hail/hail-wordpress
 * @since             1.0.0
 * @package           Hail
 *
 * @wordpress-plugin
 * Plugin Name:       Hail
 * Plugin URI:        https://github.com/hail/hail-wordpress
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.8
 * Author:            Benjamin Dawson
 * Author URI:        https://get.hail.to
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hail
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-hail-activator.php
 */
function activate_hail() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hail-activator.php';
	Hail_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-hail-deactivator.php
 */
function deactivate_hail() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hail-deactivator.php';
	Hail_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_hail' );
register_deactivation_hook( __FILE__, 'deactivate_hail' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-hail.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_hail() {

	$plugin = new Hail();
	$plugin->run();

}
run_hail();
