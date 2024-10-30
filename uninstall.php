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
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 *
 * @link       http://kount.com/woocommerce-extension
 * @since      1.0.0
 *
 * @package    Kount
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}
require_once dirname(__FILE__) . '/constants/index.php';
require_once dirname(__FILE__) . '/config/env_config.php';
require_once dirname(__FILE__) . '/config/index.php';
require_once dirname(__FILE__) . '/includes/class-kount-settings.php';
require_once dirname(__FILE__) . '/services/kfpwoo-logs.php';
$costants_obj = new KFPWOO_Constants_();
$order_metakey = $costants_obj->kfpwoo_ris_response_details();
$constants_text = $costants_obj->kfpwoo_constants_text();
$config_obj = new KFPWOO_Config_();
$site_config = $config_obj->config_();
$event_logging = new KFPWOO_Event_logging();
$options = get_option('kms');
if ($options == null) {
	$event_logging->kfpwoo_info_logs('UNINSTALL_LOGS', '', 'no stored options, uninstall is complete');
	// Clear any cached data that has been removed.
	wp_cache_flush();
	return;
}

/***API url */
$api_url = $site_config['RIS_URL'] . $site_config['INSTALL_ENDPOINT'];

/**Request parameter */
$args = array(
	'method' => 'DELETE',
	'timeout' => '5',
	'redirection' => '5',
	'httpversion' => '1.0',
	'blocking' => true,
	'headers' => array(
		'X-Kount-Hash' => $options['x_kount_hash'],
		'Content-Type' => 'application/json'
	),
	'cookies' => array(),
);
$event_logging->kfpwoo_debug_logs('UNINSTALL_LOGS', '', "API URL : " . $api_url);

try {
	$response = wp_remote_request($api_url, $args);
	if (is_wp_error($response)) {
		$event_logging->kfpwoo_error_logs('UNINSTALL_LOGS', '', __($e->getMessage(), 'kount-fraud-prevention') . "WP_Error: " . $response->get_error_message());
	} else {
		$httpcode = wp_remote_retrieve_response_code($response);
		$event_logging->kfpwoo_info_logs('UNINSTALL_LOGS', '', __('Admin settings delete', 'kount-fraud-prevention') . ", Httpcode : " . $httpcode);
	}
} catch (Exception $e) {
	$event_logging->kfpwoo_error_logs('UNINSTALL_LOGS', '', __($e->getMessage(), 'kount-fraud-prevention'));
}

// remove option from wordpress storage
$delete_success = delete_option('kms');
$event_logging->kfpwoo_info_logs('UNINSTALL_LOGS', '', 'delete_option: ' . $delete_success);

// Clear any cached data that has been removed.
wp_cache_flush();