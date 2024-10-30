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
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://kount.com/woocommerce-extension
 * @since      1.0.0
 *
 * @package    Kount
 * @subpackage Kount/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/includes
 * @author     Kount Inc. <developer@kount.com>
 */

class KFPWOO
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      KFPWOO_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('KFPWOO_VERSION')) {
			$this->version = KFPWOO_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'kount';

		$this->kfpwoo_load_variables();
		$this->kfpwoo_load_kount_merchant_settings();
		$this->kfpwoo_load_dependencies();
		$this->kfpwoo_logs();
		$this->kfpwoo_delete_logs();
		$this->kfpwoo_api_callback();
		$this->kfpwoo_set_locale();
		$this->kfpwoo_define_admin_hooks();
		$this->kfpwoo_define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - KFPWOO_Loader. Orchestrates the hooks of the plugin.
	 * - KFPWOO_i18n. Defines internationalization functionality.
	 * - KFPWOO_Admin. Defines all hooks for the admin area.
	 * - KFPWOO_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function kfpwoo_load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kount-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kount-i18n.php';

		/**
		 * The class responsible for handling all plugin text
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'constants/index.php';

		/**
		 * It stores all configuration details
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'config/index.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-kount-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-kount-public.php';

		$this->loader = new KFPWOO_Loader();
	}

	/**
	 * kfpwoo_load_kount_merchant_settings
	 * Load all settings from option table
	 * @return void
	 */
	private function kfpwoo_load_kount_merchant_settings()
	{

		/**
		 * The class responsible for defining settings
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kount-settings.php';
		KFPWOO_Merchant_Settings::kfpwoo_load();
	}

	/**
	 * kfpwoo_load_variables
	 * Load environment variables
	 * @return void
	 */
	private function kfpwoo_load_variables()
	{

		/**
		 * The class responsible for environment variables
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) .  "config/env_config.php";
	}

	/**
	 * kfpwoo_logs
	 * handling event logging
	 * @return void
	 */
	private function kfpwoo_logs()
	{
		/**
		 * The class responsible for logging
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'services/kfpwoo-logs.php';
	}

	/**
	 * delete_kount_logs
	 * handling delete older event log file
	 * @return void
	 */
	private function kfpwoo_delete_logs()
	{
		/**
		 * The class responsible for deleting old log
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'services/kfpwoo-delete-kount-logs.php';
	}

	/**
	 * kfpwoo_api_callback
	 *
	 * @return void
	 */
	private function kfpwoo_api_callback()
	{

		/**
		 * Responsible for accepting the RIS response and process the order.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'services/kfpwoo-response-api.php';
		/**
		 * Responsible for accepting the ENS response and process the order.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'services/kfpwoo-ens-updates.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the KFPWOO_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function kfpwoo_set_locale()
	{

		$plugin_i18n = new KFPWOO_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'kfpwoo_load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function kfpwoo_define_admin_hooks()
	{

		$plugin_admin = new KFPWOO_Admin($this->kfpwoo_get_plugin_name(), $this->kfpwoo_get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'kfpwoo_enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'kfpwoo_enqueue_scripts');
		$this->loader->add_action('login_init', $plugin_admin, 'kfpwoo_device_data_collector_sdk');
		$this->loader->add_action('login_init', $plugin_admin, 'kfpwoo_enqueue_ddc_scripts');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function kfpwoo_define_public_hooks()
	{

		$plugin_public = new KFPWOO_Public($this->kfpwoo_get_plugin_name(), $this->kfpwoo_get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'kfpwoo_enqueue_styles');

		$payment_enable = KFPWOO_Merchant_Settings::$is_payment_enable;
		$payment_method = KFPWOO_Merchant_Settings::$payment_method;
		$is_plugin_enable = KFPWOO_Merchant_Settings::$is_plugin_enable;
		if ($is_plugin_enable) {
			/*
			* Add Hooks on checkout order
			* woocommerce_before_checkout_form hook bind script before checkout form.
			*/
			if ($payment_enable) {
				// adding several hooks since they do not always work
				$this->loader->add_action('woocommerce_before_checkout_form_cart_notices', $plugin_public, 'kfpwoo_device_data_collector_sdk');
				$this->loader->add_action('woocommerce_before_checkout_form_cart_notices', $plugin_public, 'kfpwoo_enqueue_scripts');
				$this->loader->add_action('woocommerce_before_checkout_form', $plugin_public, 'kfpwoo_device_data_collector_sdk');
				$this->loader->add_action('woocommerce_before_checkout_form', $plugin_public, 'kfpwoo_enqueue_scripts');
				$this->loader->add_action('woocommerce_check_cart_items', $plugin_public, 'kfpwoo_device_data_collector_sdk');
				$this->loader->add_action('woocommerce_check_cart_items', $plugin_public, 'kfpwoo_enqueue_scripts');
			}

			/** Pre authorization */
			if ($payment_enable && $payment_method === 'pre') {
				// adding several hooks since they do not always work
				$this->loader->add_action( 'woocommerce_checkout_order_created',$plugin_public, 'kfpwoo_call_ris_request_pre_auth_order_created', 1, 1 );
				$this->loader->add_action( 'woocommerce_store_api_checkout_order_processed', $plugin_public,'kfpwoo_call_ris_request_pre_auth_order_processed_action', 1, 1 );
				$this->loader->add_action('woocommerce_checkout_order_processed', $plugin_public, 'kfpwoo_call_ris_request_pre_auth', 1, 3);
				add_filter('woocommerce_thankyou_order_received_text', array($plugin_public, 'kfpwoo_cancelled_order_message_pre_auth'), 12, 2);
			    $this->loader->add_action('woocommerce_order_status_changed', $plugin_public, 'kfpwoo_order_status_changed', 11, 3);
				$this->loader->add_action('woocommerce_order_payment_status_changed', $plugin_public, 'kfpwoo_process_payment_completed', 11, 2);
			}

			/** Post authorization */
			if ($payment_enable && $payment_method === 'post') {
				$this->loader->add_action('woocommerce_thankyou', $plugin_public, 'kfpwoo_call_ris_request_post_auth');
			}
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function kfpwoo_run()
	{
		$this->loader->kfpwoo_run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function kfpwoo_get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    KFPWOO_Loader    Orchestrates the hooks of the plugin.
	 */
	public function kfpwoo_get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function kfpwoo_get_version()
	{
		return $this->version;
	}
}
