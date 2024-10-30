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
 * Load kount merchant settings
 *
 * @link       http://kount.com/woocommerce-extension
 * @since      1.0.0
 *
 * @package    Kount
 * @subpackage Kount/includes
 */

/**

 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/includes
 * @author     Kount Inc. <developer@kount.com>
 */
class KFPWOO_Merchant_Settings
{
    /**
     * Merchant Account Settings
     */
    public static $is_plugin_enable;
    // Kount provided six digit ID
    public static $merchant_id;
    // When enabled we will send to sandbox environment
    public static $test_mode_enable = FALSE;

    /**
     * Payment Settings
     */
    public static $is_payment_enable = FALSE;
    public static $payment_method    = "pre";
    public static $payment_website   = 'DEFAULT';
    // URL configured in Agent Web Console for receiving Event Notification System messages
    public static $ens_url;
    // API Key : created in the API Key Management screen within the Agent Web Console (AWC).
    public static $api_key;

    // K360 API Key : created in the K360 Dashboard under Admin/Developer.  Optional at this time
    public static $k360_api_key;
    // using k360
    public static $isK360; 

    /**
     * Order ID for the current created order
     */
    public static $order_id;

    /** 
     * Internal Settings
     */
    public static $consumer_key;
    public static $consumer_secret;
    public static $store_uuid;
    public static $x_kount_hash;

    /**
     * Log Download Settings
     */
    public static $logs_level     = 'debug';
    public static $delete_logs_in = 30;

    /**
     * Customizable Cancelled Order Message Setting
     * This is displayed on the "thank you" page after Kount Declines an order in pre-authorization mode.
     */
    public static $order_cancellation_message;

    /**
     * Load settings
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function kfpwoo_load()
    {
        self::$consumer_key = 'ck_' . sha1(rand(0, 1000));
        self::$consumer_secret = 'cs_' . sha1(rand(0, 1000));
        self::$store_uuid = self::kfpwoo_store_uuid();
        if (!isset($_COOKIE['KFPWOO_SESSION_ID'])) {
            $s = KFPWOO_Merchant_Settings::kfpwoo_session_id();
            // have the client set a cookie
            setcookie('KFPWOO_SESSION_ID', $s);
            // set the super global for this running processes
            $_COOKIE['KFPWOO_SESSION_ID'] = $s;
        }

        $options = get_option(KFPWOO_SETTINGS);
        self::$merchant_id = isset($options['kount_merchant_id']) ? $options['kount_merchant_id'] : '';
        self::$api_key = isset($options['api_key']) ? $options['api_key'] : '';
        self::$k360_api_key = isset($options['k360_api_key']) ? $options['k360_api_key'] : '';
        self::$isK360 = self::$k360_api_key != '';
        self::$is_plugin_enable = (isset($options['is_plugin_enable']) && $options['is_plugin_enable'] == 1) ? TRUE : FALSE;

        // Payment Actions
        self::$is_payment_enable = (isset($options['is_payment_enable']) && $options['is_payment_enable'] == 1) ? TRUE : FALSE;
        self::$payment_method = isset($options['payment_method']) ? $options['payment_method'] : '';
        self::$payment_website = isset($options['payment_website']) ? $options['payment_website'] : 'DEFAULT';

        //Consumer key and secret key
        self::$consumer_key = isset($options['consumer_key']) ? $options['consumer_key'] : hash_hmac('sha256', self::$consumer_key, 'wc-api');
        self::$consumer_secret = isset($options['consumer_secret']) ? $options['consumer_secret'] : self::$consumer_secret;

        //Store UUID
        self::$store_uuid = isset($options['store_uuid']) ? $options['store_uuid'] : self::$store_uuid;

        //x kount hash
        self::$x_kount_hash = isset($options['x_kount_hash']) ? $options['x_kount_hash'] : '';

        //Logs level
        self::$logs_level = isset($options['logs_level']) ? $options['logs_level'] : self::$logs_level;
        self::$delete_logs_in = isset($options['delete_logs_in']) ? $options['delete_logs_in'] : self::$delete_logs_in;

        // Test Mode enable
        self::$test_mode_enable = (isset($options['test_mode_enable']) && $options['test_mode_enable'] == 1) ? TRUE : FALSE;

        //For ENS Readonly field
        if (self::$is_payment_enable == TRUE) {
            self::$ens_url = (self::$test_mode_enable ? KFPWOO_SECONDARY_ENS_URL : KFPWOO_ENS_URL) . self::$store_uuid;
        }

        //Customizable message
        self::$order_cancellation_message = isset($options['order_cancellation_message']) ? $options['order_cancellation_message'] : __('Order has been cancelled', 'kount-fraud-prevention');
    }

    /**
     * kfpwoo_store_uuid
     * Generating store uuid using store url
     * @return string
     */
    public static function kfpwoo_store_uuid()
    {
        // Get the site url to use as the basis for a UUID.
        $Input = site_url();

        // Convert the url from UTF8 to UTF16 (little endian)
        $Input = iconv('UTF-8', 'UTF-16LE', $Input);

        // Encrypt it with the MD4 hash
        $MD4Hash = hash('md4', $Input);

        // Make it uppercase, not necessary, but it's common to do so with NTLM hashes
        $NTLMHash = strtoupper($MD4Hash);

        // Return the result
        return ($NTLMHash);
    }

    /**
     * kfpwoo_session_id
     * Creates unique kount session id for each transaction
     * @return string
     */
    public static function kfpwoo_session_id()
    {
        //permitted string
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        $strength = 22;
        $input_length = strlen($permitted_chars);
        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_character = $permitted_chars[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string . time();
    }
}
