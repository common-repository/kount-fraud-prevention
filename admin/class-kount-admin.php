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
 * The admin-specific functionality of the plugin.
 *
 * @link       http://kount.com/woocommerce-extension
 * @since      1.0.0
 *
 * @package    Kount
 * @subpackage Kount/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Kount
 * @subpackage Kount/admin
 * @author     Kount Inc. <developer@kount.com>
 */
class KFPWOO_Admin
{
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
	 * plugin setting data
	 */
	private $kount_settings, $site_config, $site_constants, $event_logging, $kount_ris_response_details;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		/**configuration */
		$config_obj = new KFPWOO_Config_();
		$this->site_config = $config_obj->config_();
		$this->event_logging = new KFPWOO_Event_logging();
		$constant_obj = new KFPWOO_Constants_();
		$this->site_constants = $constant_obj->kfpwoo_constants_text();
		$this->kount_ris_response_details = $constant_obj->kfpwoo_ris_response_details();

		add_action('admin_menu', array($this, 'kfpwoo_setting_page'));
		add_action('admin_init', array($this, 'kfpwoo_setting_page_init'));

		/**adding ENS callback endpoint */
		add_action('rest_api_init', function () {
			register_rest_route('kount', 'event_response', [
				'methods' => 'POST',
				'callback' => array($this, 'kfpwoo_ens_updates'),
				'permission_callback' => '__return_true'
			]);
		});

		/**adding ENS callback endpoint */
		add_action('rest_api_init', function () {
			register_rest_route('kount', 'log', [
				'method' => 'GET',
				'callback' => array($this, 'kfpwoo_get_kount_log'),
				'permission_callback' => '__return_true'
			]);
		});

		/**
		 * Display field value on the order edit page
		 */
		add_action('woocommerce_admin_order_data_after_order_details', array($this, 'kfpwoo_display_admin_order_kount_meta'), 10, 1);
	}

	/**
	 * Load Services as required
	 */
	private function kfpwoo_load($services)
	{
		$classFileName = strtolower(str_replace('_', '-', $services));
		require_once plugin_dir_path(dirname(__FILE__)) . '/services/' . $classFileName . '.php';
		return new $services();
	}

	public function kfpwoo_device_data_collector_sdk()
	{
		$ddc_service = $this->kfpwoo_load('KFPWOO_Device_Data_Collection');
		$ddc_service->kfpwoo_device_data_collector_sdk();
	}

	/**
	 * kfpwoo_ens_updates
	 * Update order status and add notes based on ENS response
	 * @param  mixed $request
	 * @return array
	 */
	public function kfpwoo_ens_updates(WP_REST_Request $request)
	{
		$this->event_logging->kfpwoo_debug_logs("ENS_UPDATE_LOGS", '', "ens update: " . wp_json_encode($request->get_body()));

		if (!$this->kfpwoo_validate_authorization_header()) {
			$this->kfpwoo_handle_ens_update_error($this->site_constants['AUTHORIZATION_FAILED'], 401);
		}
		if (is_null($request) || empty($request->get_body())) {
			$this->kfpwoo_handle_ens_update_error($this->site_constants['DATA_NOT_FOUND'], 400);
		}
		if (!isset($request['name'])) {
			$this->kfpwoo_handle_ens_update_error($this->site_constants['ENS_WORKFLOW_MODE_REQUIRED'], 400);
		}
		if ($request['name'] != $this->site_constants['EDIT_ENS_UPDATE'] && $request['name'] != $this->site_constants['ADD_NOTES_ENS']) {
			$this->kfpwoo_handle_ens_update_error($this->site_constants['INVALID_ENS_WORKFLOW_MODE'] . " : " . $request['name'], 404);
		}

		if (isset($request['key']) && isset($request['key']['orderNumber'])) {
			$order_id = $request['key']['orderNumber'];
			$order = wc_get_order($order_id);
			if (!$order) {
				$this->kfpwoo_handle_ens_update_error($this->site_constants['INVALID_ORDER_ID_ERROR'] . " : Failed to get order " . $order_id, 404);
			}
			if (!isset($request['key']['transactionID'])) {
				$this->kfpwoo_handle_ens_update_error($this->site_constants['TRANSACTION_ID_NOT_FOUND'] . " : Missing transaction ID for order " . $order_id, 400);
			}

			$kount_trans_id = $order->get_meta('kount_transaction_id', true);
			$request_kount_trans_id = $request['key']['transactionID'];
			if ($kount_trans_id != $request_kount_trans_id) {
				$this->kfpwoo_handle_ens_update_error($this->site_constants['INVALID_KOUNT_TRANSACTION_ID'] . " : " . $request_kount_trans_id . " does not match RIS transaction: " . $kount_trans_id . " for order: " . $order_id, 409);
			}

			KFPWOO_ENS_Updates::kfpwoo_update_order($request);
		}

		return [];
	}

	/**
	 * kfpwoo_get_kount_log
	 * retrieve the kount log
	 * @param  mixed $request
	 * @return string
	 */
	public function kfpwoo_get_kount_log(WP_REST_Request $request)
	{
		if (!$this->kfpwoo_validate_authorization_header()) {
			$this->kfpwoo_handle_ens_update_error($this->site_constants['AUTHORIZATION_FAILED'], 401);
		}

		$response = new WP_REST_Response;
		$logging = new KFPWOO_Event_logging();
		$path = $logging->kfpwoo_get_log_filename() . '-' . $request->get_param("date");
		$name = basename($path);

		// get the file mime type 
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime_type = finfo_file($finfo, $path);

		// tell the browser what it's about to receive
		header("Content-Disposition: attachment; filename=$name;");
		header("Content-Type: $mime_type");
		header("Content-Description: File Transfer");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . filesize($path));
		header("Cache-Control: no-cache private");

		// stream the file without loading it into RAM completely
		$fp = fopen($path, 'rb');
		fpassthru($fp);

		// kill WP
		exit;
	}

	/**
	 * kfpwoo_update_meta_field
	 * Responsible for updating meta field value and log
	 * @param  mixed $user_id
	 * @param  mixed $meta_key
	 * @param  mixed $meta_value
	 * @return void
	 */
	public function kfpwoo_update_and_log_meta_field($user_id, $meta_key, $meta_value)
	{
		$update_user_meta_result = update_user_meta($user_id, $meta_key, $meta_value);
		$this->event_logging->kfpwoo_debug_logs(
			"ENS_UPDATE_LOGS",
			'',
			"called update_user_meta(" . $user_id . ", " . $meta_key . ", " . $meta_value . ") result: " . $update_user_meta_result
		);
	}

	/**
	 * kfpwoo_handle_ens_update_error
	 * Log error message and exit with response code
	 * @param mixed $message
	 * @param mixed $response_code
	 * @return void
	 */
	public function kfpwoo_handle_ens_update_error($message, $response_code)
	{
		$this->event_logging->kfpwoo_debug_logs("ENS_UPDATE_LOGS", '', $message);
		http_response_code($response_code);
		$response = [
			$this->site_constants['RESPONSE_STATUS'] => false,
			$this->site_constants['RESPONSE_MESSAGE'] => $message
		];
		echo wp_json_encode($response);
		exit;
	}

	/**
	 * kfpwoo_validate_authorization_header
	 * Validate RIS endpoints header
	 * @return bool
	 */
	public function kfpwoo_validate_authorization_header()
	{
		$headers = array_change_key_case(apache_request_headers());
		if (!isset($headers['authorization'])) {
			return false;
		}

		$wc_header = 'Bearer ' . base64_encode(KFPWOO_Merchant_Settings::$consumer_key . KFPWOO_Merchant_Settings::$consumer_secret);
		if ($headers['authorization'] != $wc_header) {
			return false;
		}

		return true;
	}

	/**
	 * kfpwoo_display_ris
	 * displaying kount ris response details on order page
	 * @param  mixed $order
	 * @return void
	 */
	private function kfpwoo_display_ris($order): void
	{
		echo '<div style="padding-top:20px; clear:both;"><h3>' . esc_attr($this->site_constants['KOUNT_RESPONSE']) . '</h3><p>';
		foreach ($this->kount_ris_response_details as $key => $value) {
			if ($key == 'KOUNT_TRANSACTION_ID') {
				$tran = $order->get_meta($value, true);
				$awc = $this->site_config['AWC_URL'] . $tran;
				echo '<strong>' . esc_attr($this->site_constants[$key]) . ' : </strong> <a href="' . $awc . '" target="_blank">' . $tran . '</a> <br />';
			} else
				echo '<strong>' . esc_attr($this->site_constants[$key]) . ' : </strong> ' . esc_attr($order->get_meta($value, true)) . '<br />';
		}
		echo '</p></div>';
	}

	/**
	 * kfpwoo_display_k360
	 * displaying kount k360 response details on order page
	 * @param  mixed $order
	 * @return void
	 */
	private function kfpwoo_display_k360($order): void
	{
		echo '<div style="padding-top:20px; clear:both;"><h3>' . esc_attr($this->site_constants['KOUNT_RESPONSE']) . '</h3><p>';

		echo '<strong>Omniscore: </strong> ' . esc_attr($order->get_meta($this->kount_ris_response_details['KOUNT_RIS_OMNISCORE'], true)) . '<br />';
		echo '<strong>Response: </strong> ' . esc_attr($order->get_meta($this->kount_ris_response_details['KOUNT_RIS_RESPONSE'], true)) . '<br />';
		echo '<strong>Response Reason: </strong> ' . esc_attr($order->get_meta($this->kount_ris_response_details['KOUNT_RIS_RESPONSE_REASON'], true)) . '<br />';
		echo '<strong>Rules Triggered: </strong> ' . esc_attr($order->get_meta($this->kount_ris_response_details['KOUNT_RIS_RULES_TRIGGERED'], true)) . '<br />';

		$tran = $order->get_meta($this->kount_ris_response_details['KOUNT_TRANSACTION_ID'], true);
		$k360 = $this->site_config['K360_URL'] . $tran;
		echo '<strong>' . esc_attr($this->site_constants['KOUNT_TRANSACTION_ID']) . ' : </strong> <a href="' . $k360 . '" target="_blank">' . $tran . '</a> <br />';
		echo '<strong>' . esc_attr($this->site_constants['KOUNT_KAPT']) . ' : </strong> ' . esc_attr($order->get_meta($this->kount_ris_response_details['KOUNT_KAPT'], true)) . '<br />';
		echo '<strong>' . esc_attr($this->site_constants['KOUNT_CARDS']) . ' : </strong> ' . esc_attr($order->get_meta($this->kount_ris_response_details['KOUNT_CARDS'], true)) . '<br />';
		echo '<strong>' . esc_attr($this->site_constants['KOUNT_EMAIL']) . ' : </strong> ' . esc_attr($order->get_meta($this->kount_ris_response_details['KOUNT_EMAIL'], true)) . '<br />';
		echo '<strong>' . esc_attr($this->site_constants['KOUNT_DEVICES']) . ' : </strong> ' . esc_attr($order->get_meta($this->kount_ris_response_details['KOUNT_DEVICES'], true)) . '<br />';

		echo '</p></div>';
	}

	/**
	 * kfpwoo_display_admin_order_kount_meta
	 * displaying kount ris response details on order page
	 * @param  mixed $order
	 * @return void
	 */
	public function kfpwoo_display_admin_order_kount_meta($order)
	{
		if (KFPWOO_Merchant_Settings::$isK360) {
			$this->kfpwoo_display_k360($order);
		} else {
			$this->kfpwoo_display_ris($order);
		}
	}

	/**
	 * kfpwoo_setting_page
	 * Kount setting page adding menu
	 * @return void
	 */
	public function kfpwoo_setting_page()
	{
		add_menu_page(
			'Kount', // page_title
			'Kount', // menu_title
			'manage_options', // capability
			'kount', // menu_slug
			array($this, 'kfpwoo_kount_create_setting_page'), // function
			'dashicons-hammer', // icon_url
			76 // position
		);
	}
	
	/**
	 * kfpwoo_setting_page_init
	 * It will render a page when kount clicked
	 * @return void
	 */
	public function kfpwoo_setting_page_init()
	{

		register_setting(
			'kfpwoo_option_group', // option_group
			KFPWOO_SETTINGS, // option_name
			array($this, 'kfpwoo_sanitize') // sanitize_callback
		);

		// Account Section
		$account_information = 'kount_account_information';
		$this->kfpwoo_add_section($account_information, '', 'account-information');
		$this->kfpwoo_add_input('is_plugin_enable', __('Enable Plugin', 'kount-fraud-prevention'), 'kfpwoo_enable_plugin_callback', $account_information, 'account-information');
		$this->kfpwoo_add_input('kount_merchant_id', __('Client/Merchant ID *', 'kount-fraud-prevention'), 'kfpwoo_merchant_id_callback', $account_information, 'account-information');
		$this->kfpwoo_add_input('test_mode_enable', __('Enable Test Mode', 'kount-fraud-prevention'), 'kfpwoo_enable_test_mode_callback', $account_information, 'account-information');

		// Payment Section
		$payment_section = 'kount_payment_section';
		$this->kfpwoo_add_section($payment_section, '', 'payment-functionality');
		$this->kfpwoo_add_input('is_payment_enable', __('Payment Risk Assessment', 'kount-fraud-prevention'), 'kfpwoo_enable_payment_callback', $payment_section, 'payment-functionality');
		$this->kfpwoo_add_input('payment_method', __('Payment Workflow Mode', 'kount-fraud-prevention'), 'kfpwoo_select_payment_method_callback', $payment_section, 'payment-functionality');
		$this->kfpwoo_add_input('api_key', __('Kount Command API Key', 'kount-fraud-prevention'), 'kfpwoo_api_key_callback', $payment_section, 'payment-functionality');
		$this->kfpwoo_add_input('k360_api_key', __('Kount 360 API Key', 'kount-fraud-prevention'), 'kfpwoo_k360_api_key_callback', $payment_section, 'payment-functionality');
		$this->kfpwoo_add_input('ens_url', __('ENS Callback URL', 'kount-fraud-prevention'), 'kfpwoo_ens_url_callback', $payment_section, 'payment-functionality');
		$this->kfpwoo_add_input('payment_website', __('Website ID *', 'kount-fraud-prevention'), 'kfpwoo_payment_website_callback', $payment_section, 'payment-functionality');
		$this->kfpwoo_add_input('order_cancellation_message', __('Order Cancellation Message *', 'kount-fraud-prevention'), 'kfpwoo_order_cancellation_message_callback', $payment_section, 'payment-functionality');

		// Account Logging Section
		$event_logging_section = 'KFPWOO_Event_logging_section';
		$this->kfpwoo_add_section($event_logging_section, '', 'event_logging');
		$this->kfpwoo_add_input('logs_level', __('Select logs level', 'kount-fraud-prevention'), 'kfpwoo_select_log_level_callback', $event_logging_section, 'event_logging');
		$this->kfpwoo_add_input('delete_logs_in', __('Logs delete duration (in days)', 'kount-fraud-prevention'), 'kfpwoo_delete_logs_in_callback', $event_logging_section, 'event_logging');
	}

	/**
	 * kfpwoo_add_input
	 * Add input fields for kount admin configuration settings UI
	 * @param  mixed $id
	 * @param  mixed $name
	 * @param  mixed $callback
	 * @param  mixed $section_id
	 * @param  mixed $page
	 * @return void
	 */
	public function kfpwoo_add_input($id, $name, $callback, $section_id, $page)
	{
		add_settings_field(
			$id, // id
			$name, // title
			array($this, $callback), // callback
			$page, // page
			$section_id // section
		);
	}

	/**
	 * kfpwoo_add_section
	 * Add section for kount admin configuration settings UI
	 * @param  mixed $section_id
	 * @param  mixed $section_title
	 * @param  mixed $page
	 * @param  mixed $callback
	 * @return void
	 */
	public function kfpwoo_add_section($section_id, $section_title, $page, $callback = 'kfpwoo_section_info')
	{
		add_settings_section(
			$section_id, // id
			$section_title, // title
			array($this, $callback), // callback
			$page // page
		);
	}

	/**
	 * kfpwoo_sanitize
	 * Get admin configuration form values and save them into DB
	 * @param  mixed  $input
	 * @return array | void
	 */
	public function kfpwoo_sanitize($input)
	{
		$sanitary_values = array();

		if (isset($input['is_payment_enable'])) {
			$sanitary_values['is_payment_enable'] = sanitize_text_field($input['is_payment_enable']);
		}
		if (isset($input['payment_method'])) {
			$sanitary_values['payment_method'] = sanitize_text_field($input['payment_method']);
		}
		if (isset($input['kount_merchant_id'])) {
			$sanitary_values['kount_merchant_id'] = sanitize_text_field($input['kount_merchant_id']);
		}

		$sanitary_values['store_uuid'] = KFPWOO_Merchant_Settings::$store_uuid;

		if (isset($input['regenerate_keys']) && $input['regenerate_keys'] == true) {

			$sanitary_values['regenerate_keys'] = true;
			$updated_date = new DateTime();
			$last_updated_date = $updated_date->format('Y-m-d\TH:i:s.') . substr($updated_date->format('u'), 0, 3) . 'Z';
			$sanitary_values['last_regenerate_keys_datetime'] = $last_updated_date;
			$sanitary_values['consumer_key'] = hash_hmac('sha256', 'ck_' . sha1(rand()), 'wc-api');
			$sanitary_values['consumer_secret'] = 'cs_' . sha1(rand());
		} else {
			$sanitary_values['consumer_key'] = KFPWOO_Merchant_Settings::$consumer_key;
			$sanitary_values['consumer_secret'] = KFPWOO_Merchant_Settings::$consumer_secret;
		}

		if (isset($input['api_key'])) {
			$sanitary_values['api_key'] = sanitize_text_field($input['api_key']);
		}

		if (isset($input['k360_api_key'])) {
			$sanitary_values['k360_api_key'] = sanitize_text_field($input['k360_api_key']);
		}

		if (isset($input['payment_website'])) {
			$sanitary_values['payment_website'] = sanitize_text_field($input['payment_website']);
		}

		if (isset($input['ens_url'])) {
			$sanitary_values['ens_url'] = sanitize_text_field($input['ens_url']);
		}

		if (isset($input['order_cancellation_message'])) {
			$sanitary_values['order_cancellation_message'] = sanitize_text_field($input['order_cancellation_message']);
		}

		if (isset($input['is_plugin_enable'])) {
			$sanitary_values['is_plugin_enable'] = sanitize_text_field($input['is_plugin_enable']);
		}

		if (isset($input['test_mode_enable'])) {
			$sanitary_values['test_mode_enable'] = sanitize_text_field($input['test_mode_enable']);
		}

		if (isset($input['environment_type'])) {
			$sanitary_values['environment_type'] = sanitize_text_field($input['environment_type']);
		}

		if (isset($input['logs_level'])) {
			$sanitary_values['logs_level'] = sanitize_text_field($input['logs_level']);
		}

		if (isset($input['delete_logs_in'])) {
			$sanitary_values['delete_logs_in'] = sanitize_text_field($input['delete_logs_in']);
		}

		$options_data = get_option(KFPWOO_SETTINGS);
		$test_changed = (isset($input['test_mode_enable']) xor KFPWOO_Merchant_Settings::$test_mode_enable);
		if (!$options_data || $test_changed) {
			$api_method = 'POST';
		} else {
			$api_method = 'PUT';
		}
		$headers = array(
			'X-Kount-Hash' => KFPWOO_Merchant_Settings::$x_kount_hash,
			'Authorization' => 'Bearer ' . (isset($input['api_key']) ? $input['api_key'] : ''),
			'Content-Type' => 'application/json'
		);

		$api_url = (isset($input['test_mode_enable']) ? KFPWOO_SECONDARY_REQUEST_ROUTER_URL : KFPWOO_REQUEST_ROUTER_URL) . $this->site_config['INSTALL_ENDPOINT'];

		/***configuration payload */
		$payload = (object) [
			$this->site_constants['WOO_STORE_ID'] => (isset($sanitary_values["store_uuid"]) ? sanitize_text_field($sanitary_values["store_uuid"]) : ''),
			$this->site_constants['KOUNT_MERCHANT_ID'] => (isset($sanitary_values["kount_merchant_id"]) ? sanitize_text_field($sanitary_values["kount_merchant_id"]) : ''),
			$this->site_constants['KOUNT_PAYMENT_SITE_ID'] => (isset($sanitary_values["payment_website"]) ? sanitize_text_field($sanitary_values["payment_website"]) : ''),
			$this->site_constants['WOO_AUTHENTICATION_TOKEN'] => (isset($sanitary_values["consumer_secret"]) ? sanitize_text_field($sanitary_values["consumer_key"] . $sanitary_values["consumer_secret"]) : ''),
			$this->site_constants['WOO_API_BASE_URL'] => get_rest_url() . $this->site_config['WOO_API_ENDPOINTS'],
			$this->site_constants['K360_API_KEY'] => (isset($sanitary_values["k360_api_key"]) ? sanitize_text_field($sanitary_values["k360_api_key"]) : ''),
		];
		/**Request parameter */
		$args = array(
			'body' => wp_json_encode($payload),
			'method' => $api_method,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
			'cookies' => array(),
		);
		/**Handle response */
		try {
			$this->event_logging->kfpwoo_debug_logs($this->site_constants['INSTALL_LOGS'], '', $this->site_constants['LOG_API_URL'] . " : " . $api_url);
			$this->event_logging->kfpwoo_debug_logs($this->site_constants['INSTALL_LOGS'], '', $this->site_constants['LOG_METHOD'] . " : " . $api_method);
			$this->event_logging->kfpwoo_debug_logs($this->site_constants['INSTALL_LOGS'], '', 'install payload" : ' . $args['body']);
			$response = wp_remote_request($api_url, $args);
			if (is_wp_error($response)) {
				$this->event_logging->kfpwoo_info_logs($this->site_constants['INSTALL_LOGS'], '', 'Failure with WP_Error: ' . $response->get_error_message());
			} else {
				$httpcode = wp_remote_retrieve_response_code($response);
				$response = wp_remote_retrieve_body($response);
				if ($httpcode == 200) {
					if ($api_method == "POST") {
						$response = json_decode($response);
						$x_kount_header = $response->kountHeaderHash;
						$sanitary_values['x_kount_hash'] = $x_kount_header;
						$this->event_logging->kfpwoo_info_logs($this->site_constants['INSTALL_LOGS'], '', $this->site_constants['SETTINGS_SAVED_SUCCESSFULLY'] . ", Httpcode : " . $httpcode);
					} else {
						$sanitary_values['x_kount_hash'] = KFPWOO_Merchant_Settings::$x_kount_hash;
						$this->event_logging->kfpwoo_info_logs($this->site_constants['INSTALL_LOGS'], '', $this->site_constants['SETTING_UPDATED_SUCCESSFULLY'] . ", Httpcode : " . $httpcode);
					}
					return $sanitary_values;
				}
			}

			$this->event_logging->kfpwoo_info_logs($this->site_constants['INSTALL_LOGS'], '', "Saving config to Kount failed.  Latest changes were lost. " . $response . ", http code:" . $httpcode);
			$this->kfpwoo_install_response("Failure! Settings were not saved.  The last changes were lost.<br><br>HTTP Error: " . $httpcode . "<br><small>" . $response);
			return $options_data;
		} catch (Exception $e) {
			$this->kfpwoo_install_response(__($e->getMessage(), 'kount-fraud-prevention'));
		}
	}

	public function kfpwoo_merchant_id_callback()
	{
		printf(
			'<input class="regular-text" type="text" name="' . KFPWOO_SETTINGS . '[kount_merchant_id]" id="kount_merchant_id" value="%s"><br/><span class="toggle-description">%s</span><input class="regular-text" type="hidden" name="' . KFPWOO_SETTINGS . '[regenerate_keys]" id="regenerate_keys" value="false"><input class="regular-text" type="hidden" name="' . KFPWOO_SETTINGS . '[store_uuid]" id="store_uuid" value="%s">',
			isset(KFPWOO_Merchant_Settings::$merchant_id) ? esc_attr(KFPWOO_Merchant_Settings::$merchant_id) : '',
			"The Kount provided Merchant or K360 Client ID",
			isset(KFPWOO_Merchant_Settings::$store_uuid) ? esc_attr(KFPWOO_Merchant_Settings::$store_uuid) : ''
		);
	}

	public function kfpwoo_api_key_callback()
	{
		printf(
			'<input class="regular-text" type="text" name="' . KFPWOO_SETTINGS . '[api_key]" id="api_key" value="%s"><br /><span class="toggle-description">%s</span>',
			isset(KFPWOO_Merchant_Settings::$api_key) ? esc_attr(KFPWOO_Merchant_Settings::$api_key) : '',
			"The API Key created in the API Key management screen within the Agent Web Console (AWC)."
		);
	}

	public function kfpwoo_k360_api_key_callback()
	{
		printf(
			'<input class="regular-text" type="text" name="' . KFPWOO_SETTINGS . '[k360_api_key]" id="k360_api_key" value="%s"><br /><span class="toggle-description">%s</span>',
			isset(KFPWOO_Merchant_Settings::$k360_api_key) ? esc_attr(KFPWOO_Merchant_Settings::$k360_api_key) : '',
			"The K360 API Key created in K360 Dashboard."
		);
	}

	public function kfpwoo_payment_website_callback()
	{
		printf(
			'<input class="regular-text" type="text" name="' . KFPWOO_SETTINGS . '[payment_website]" id="payment_website" value="%s"><br /><span class="toggle-description">%s</span>',
			isset(KFPWOO_Merchant_Settings::$payment_website) ? esc_attr(KFPWOO_Merchant_Settings::$payment_website) : '',
			"The website setup in Agent Web Console (AWC) or use 'Default' (unused for K360)"
		);
	}

	public function kfpwoo_order_cancellation_message_callback()
	{
		printf(
			'<input class="regular-text" type="text" name="' . KFPWOO_SETTINGS . '[order_cancellation_message]" id="order_cancellation_message" value="%s"><br />',
			isset(KFPWOO_Merchant_Settings::$order_cancellation_message) ? esc_html(KFPWOO_Merchant_Settings::$order_cancellation_message) : ''
		);
	}


	public function kfpwoo_ens_url_callback()
	{
		printf(
			'<input class="regular-text" type="text" readonly name="' . KFPWOO_SETTINGS . '[ens_url]" id="ens_url" value="%s"><br /><span class="toggle-description">%s</span>',
			isset(KFPWOO_Merchant_Settings::$ens_url) ? esc_url(KFPWOO_Merchant_Settings::$ens_url) : '',
			"URL configured in the Agent Web Console (AWC) for receiving Event Notification System messages."
		);
	}


	public function kfpwoo_enable_payment_callback()
	{
		printf(
			'<label class="switch">
				<input name="' . KFPWOO_SETTINGS . '[is_payment_enable]" type="checkbox" id="is_payment_enable" value="1" %s>
				<span class="slider round"></span>
			</label><br/><span class="toggle-description">%s</span>',
			isset(KFPWOO_Merchant_Settings::$is_payment_enable) && KFPWOO_Merchant_Settings::$is_payment_enable ? 'checked' : '',
			"When enabled, It will call to Kount to assess the payment risk."
		);
	}

	public function kfpwoo_enable_plugin_callback()
	{
		printf(
			'<label class="switch">
				<input name="' . KFPWOO_SETTINGS . '[is_plugin_enable]" type="checkbox" id="is_plugin_enable" value="1" %s>
				<span class="slider round"></span>
			</label><br/><span class="toggle-description">%s</span>',
			isset(KFPWOO_Merchant_Settings::$is_plugin_enable) && KFPWOO_Merchant_Settings::$is_plugin_enable ? 'checked' : '',
			"When enabled, It will call to Kount to assess the payment risk."
		);
	}

	public function kfpwoo_enable_test_mode_callback()
	{
		$this->kfpwoo_show_boolean(
			'test_mode_enable',
			KFPWOO_Merchant_Settings::$test_mode_enable,
			'When enabled, the test environment of Kount will be used. Note: adjust API Keys (and Website ID if necessary) when toggling this.'
		);
	}

	private function kfpwoo_show_boolean($setting_name, $value, $description)
	{
		printf(
			'<label class="switch"> <input name="' . KFPWOO_SETTINGS . '[%s]" type="checkbox" id="%s" value="1" %s> <span class="slider round"></span> </label><br/><span class="toggle-description">%s</span>',
			$setting_name,
			$setting_name,
			isset($value) && $value ? 'checked' : '',
			$description
		);
	}

	public function kfpwoo_select_payment_method_callback()
	{
		printf(
			'<select name="' . KFPWOO_SETTINGS . '[payment_method]" id="payment_method" class="regular-text">
				<option value="pre" %s>Pre-Authorization</option>
				<option value="post" %s>Post-Authorization</option>
			</select>',
			isset(KFPWOO_Merchant_Settings::$payment_method) && KFPWOO_Merchant_Settings::$payment_method && KFPWOO_Merchant_Settings::$payment_method == "pre" ? 'selected' : '',
			isset(KFPWOO_Merchant_Settings::$payment_method) && KFPWOO_Merchant_Settings::$payment_method && KFPWOO_Merchant_Settings::$payment_method == "post" ? 'selected' : ''
		);
	}

	public function kfpwoo_delete_logs_in_callback()
	{
		printf(
			'<input class="regular-text" type="number"  name="' . KFPWOO_SETTINGS . '[delete_logs_in]" id="delete_logs_in" value="%s">',
			isset(KFPWOO_Merchant_Settings::$delete_logs_in) ? esc_attr(KFPWOO_Merchant_Settings::$delete_logs_in) : ''
		);
	}

	public function kfpwoo_kount_create_setting_page()
	{
		$this->kount_settings = get_option(KFPWOO_SETTINGS);
		require_once plugin_dir_path(__FILE__) . 'partials/kount-admin-display.php';
	}

	public function kfpwoo_select_log_level_callback()
	{
		printf(
			'<select name="' . KFPWOO_SETTINGS . '[logs_level]" id="logs_level" class="regular-text">
				<option value="debug" %s>Debug</option>
				<option value="error" %s>Error</option>
				<option value="info" %s>Info</option>
			</select>',
			isset(KFPWOO_Merchant_Settings::$logs_level) && KFPWOO_Merchant_Settings::$logs_level && KFPWOO_Merchant_Settings::$logs_level == "debug" ? 'selected' : '',
			isset(KFPWOO_Merchant_Settings::$logs_level) && KFPWOO_Merchant_Settings::$logs_level && KFPWOO_Merchant_Settings::$logs_level == "error" ? 'selected' : '',
			isset(KFPWOO_Merchant_Settings::$logs_level) && KFPWOO_Merchant_Settings::$logs_level && KFPWOO_Merchant_Settings::$logs_level == "info" ? 'selected' : ''
		);
	}

	public function kfpwoo_section_info()
	{
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function kfpwoo_enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in KFPWOO_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The KFPWOO_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/kount-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function kfpwoo_enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in KFPWOO_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The KFPWOO_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/kount-admin.js', array('jquery'), $this->version, false);
	}

	public function kfpwoo_enqueue_ddc_scripts()
	{
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/kount-admin-ddc.js', array('jquery'), $this->version, false);
	}

	/**
	 * Prepare an error response, and log that response message
	 *
	 * @param $message  The constant message
	 * @return void
	 *
	 * @since 1.0.7
	 */
	public function kfpwoo_install_response($message)
	{
		$this->event_logging->kfpwoo_error_logs($this->site_constants['INSTALL_LOGS'], '', $message);
		add_settings_error(KFPWOO_SETTINGS, esc_attr('request'), $message);
	}
}
