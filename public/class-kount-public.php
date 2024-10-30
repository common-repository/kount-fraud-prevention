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
 * The public-facing functionality of the plugin.
 *
 * @link       http://kount.com/woocommerce-extension
 * @since      1.0.0
 *
 * @package    Kount
 * @subpackage Kount/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Kount
 * @subpackage Kount/public
 * @author     Kount Inc. <developer@kount.com>
 */
class KFPWOO_Public {

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

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function kfpwoo_enqueue_styles() {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kount-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function kfpwoo_enqueue_scripts() {

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/kount-public.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Load Services as required
	*/
	private function kfpwoo_load($services){
		$classFileName = strtolower(str_replace('_','-', $services));
		require_once plugin_dir_path( dirname( __FILE__ ) ).'/services/' . $classFileName . '.php';
		return new $services();
	}


	/**
	 * kfpwoo_call_ris_request_pre_auth
	 * It will call when user place order for pre auth
	 * @return void
	 */
	public function kfpwoo_call_ris_request_pre_auth($order_id, $posted_data, $order ){
		$pre_auth = $this->kfpwoo_load('KFPWOO_Pre_Auth');
		$pre_auth->kfpwoo_get_checkout_page_form_data($order_id);
	}


	/**
	 * kfpwoo_call_ris_request_pre_auth_order_created
	 * It will call when user place order for pre auth
	 * @return void
	 */
	public function kfpwoo_call_ris_request_pre_auth_order_created($order ){
		$pre_auth = $this->kfpwoo_load('KFPWOO_Pre_Auth');
		$pre_auth->kfpwoo_get_checkout_page_form_data($order->id);
	}

	
	/**
	 * kfpwoo_call_ris_request_pre_auth_order_processed_action
	 * It will call when user place order for pre auth
	 * @return void
	 */
	public function kfpwoo_call_ris_request_pre_auth_order_processed_action($order ){
		$pre_auth = $this->kfpwoo_load('KFPWOO_Pre_Auth');
		$pre_auth->kfpwoo_get_checkout_page_form_data($order->id);
	}


	/**
	 * kfpwoo_process_payment_completed
	 * Calling RIS API in Mode U to update the transaction status from gateway
	 * @param  mixed $order_id
	 * @param  mixed $order
	 * @return void
	 */
	public function kfpwoo_process_payment_completed($order_id, $order){
		$pre_auth = $this->kfpwoo_load('KFPWOO_Pre_Auth');
		$pre_auth->kfpwoo_call_ris_mode_update($order_id, $order);
	}

	/**
	 * kfpwoo_order_status_changed
	 * Move order status to On Hold in Auto=R
	 * @param  mixed $order_id
	 * @param  mixed $status_from
	 * @param  mixed $status_new
	 * @return void
	 */
	public function kfpwoo_order_status_changed($order_id, $status_from, $status_new){
		$pre_auth = $this->kfpwoo_load('KFPWOO_Pre_Auth');
		$pre_auth->kfpwoo_hold_if_review($order_id, $status_from, $status_new);
	}

	/**
	 * kfpwoo_cancelled_order_message_pre_auth
	 * It will call if order has been cancelled for pre auth
	 * @param  mixed $text
	 * @param  mixed $order
	 * @return void
	 */
	public function kfpwoo_cancelled_order_message_pre_auth($text, $order){
		if ($order != null){
			$decision = $order->get_meta('kount_RIS_response', true);
			if($decision === 'D'){
				$text = KFPWOO_Merchant_Settings::$order_cancellation_message;
			}
		}
		return $text;
	}

	/**
	 * kfpwoo_device_data_collector_sdk
	 * While user login /customer checkout collect the device data information
	 * @return void
	 */
	public function kfpwoo_device_data_collector_sdk(){
		$ddc_service = $this->kfpwoo_load('KFPWOO_Device_Data_Collection');
		$ddc_service->kfpwoo_device_data_collector_sdk();
	}

	/**
	 * kfpwoo_call_ris_request_post_auth
	 * It will call when payment success for post auth
	 * @return void
	 */
	public function kfpwoo_call_ris_request_post_auth($order_id){
		$post_auth = $this->kfpwoo_load('KFPWOO_Post_Auth');
		$post_auth->kfpwoo_post_auth_payment_success($order_id);
	}
}
