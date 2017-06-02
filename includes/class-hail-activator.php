<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/hail/hail-wordpress
 * @since      1.0.0
 *
 * @package    Hail
 * @subpackage Hail/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Hail
 * @subpackage Hail/includes
 * @author     Benjamin Dawson <ben@hail.to>
 */
class Hail_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if (!wp_next_scheduled('hail_cron_import')) {
			wp_schedule_event(time(), 'hourly', 'hail_cron_import');
		}
	}

}
