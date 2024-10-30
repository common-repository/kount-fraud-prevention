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
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://kount.com/woocommerce-extension
 * @since      1.0.0
 *
 * @package    Kount
 * @subpackage Kount/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/includes
 * @author     Kount Inc. <developer@kount.com>
 */
class KFPWOO_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function kfpwoo_load_plugin_textdomain() {

		load_plugin_textdomain(
			'kount',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}