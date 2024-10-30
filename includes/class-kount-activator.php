<?php
/**
 * Copyright (c) 2021 Kount, Inc.

 * This file is part of Kount Fraud Prevention.

 * Kount Fraud Prevention is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Kount Fraud Prevention is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kount Fraud Prevention.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Fired during plugin activation
 *
 * @link       http://kount.com/woocommerce-extension
 * @since      1.0.0
 *
 * @package    Kount
 * @subpackage Kount/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/includes
 * @author     Kount Inc. <developer@kount.com>
 */
class KFPWOO_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function kfpwoo_activate() {

		/**
		 * Check if WooCommerce is active
		 **/
		if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			echo '<h3>' . esc_html(__("Please install WooCommerce before using this plugin", 'kount-fraud-prevention')) . '</h3>';
			@trigger_error(__("Please install WooCommerce before using this plugin", 'kount-fraud-prevention'), E_USER_ERROR);
		}
		else{
			$uploads      =   wp_upload_dir();
			$upload_dir   =   $uploads['basedir'] ; //base directory url
			$upload_dir   =   $upload_dir . '/wc-logs'; //logs folder url
			/**create directory if not exists */
			if (! is_dir($upload_dir)) {
				mkdir( $upload_dir, 0700 );
			}
		}

	}

}
