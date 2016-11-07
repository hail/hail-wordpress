<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/hail/hail-wordpress
 * @since      1.0.0
 *
 * @package    Hail
 * @subpackage Hail/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Hail
 * @subpackage Hail/includes
 * @author     Benjamin Dawson <ben@hail.to>
 */
class Hail_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		if (wp_next_scheduled('hail_cron')) {
			wp_clear_scheduled_hook('hail_cron_import');
		}
	}

}
