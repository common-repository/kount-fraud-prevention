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
 * Fired during plugin deactivation
 *
 * @link       http://kount.com/woocommerce-extension
 * @since      1.0.0
 *
 * @package    Kount
 * @subpackage Kount/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/includes
 * @author     Kount Inc. <developer@kount.com>
 */
class KFPWOO_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function kfpwoo_deactivate() {
		add_filter('manage_users_columns','kfpwoo_remove_users_columns');
	}

	/**
	 * kfpwoo_remove_users_columns
	 * Removing user filed added for account creation kount status
	 * @param  mixed $column_headers
	 * @return void
	 */
	function kfpwoo_remove_users_columns($column_headers) {
		unset($column_headers['kount_account_status']);
		return $column_headers;
	}


}
