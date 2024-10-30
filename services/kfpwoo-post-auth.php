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
 * KFPWOO_Post_Auth
 *
 * This is used to class to collect post auth payment data and sent to the Kount Server
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/PostAuth
 * @author     Kount Inc. <developer@kount.com>
 *
 */
require_once 'kfpwoo-api-call.php';;

class KFPWOO_Post_Auth
{

    /**public variable*/
    public $order;
    /**Private variables*/
    private $call_api_obj, $event_logging, $coupon_total, $payment_status, $constants_text, $payment_type, $order_status, $kount_auto_status, $kount_ris_response_details, $site_config, $discount;

    /**
     * __construct
     * It will call automatically when class object created
     * @return void
     */
    public function __construct()
    {
        //logs class object (responsible for logs)
        $this->event_logging        =  new KFPWOO_Event_logging();
        //current session id
        //Http call
        $this->call_api_obj         =  new KFPWOO_Call_API();
        /**Constants */
        $costants_obj               =   new KFPWOO_Constants_();
        $this->constants_text       =   $costants_obj->kfpwoo_constants_text();
        $this->payment_type         =   $costants_obj->kfpwoo_payment_type();
        $this->order_status         =   $costants_obj->kfpwoo_order_status();
        $this->kount_auto_status    =   $costants_obj->kfpwoo_auto_status();
        $this->kount_ris_response_details   =   $costants_obj->kfpwoo_ris_response_details();
        /**configuration */
        $config_obj                 =   new KFPWOO_Config_();
        $this->site_config          =   $config_obj->config_();
    }

    /**
     * kfpwoo_post_auth_payment_success
     * Set orderid and call RIS API
     * @param  mixed $order_id
     * @return void
     */
    public function kfpwoo_post_auth_payment_success($order_id)
    {
        if (!$this->kfpwoo_set_order($order_id)) return;
        if (!$this->order->get_meta('kount_ris_is_assessment_sent', true)) {
            $this->order->update_meta_data('kount_ris_is_assessment_sent', true);
            $this->order->save();
            $this->payment_status = $this->constants_text['PAYMENT_SUCCESS_STATUS'];
            $this->kfpwoo_http_request();
            $current_session_id = sanitize_text_field($_COOKIE['KFPWOO_SESSION_ID']);
            $this->event_logging->kfpwoo_info_logs($this->constants_text['POST_AUTH_LOGS'], $current_session_id, $this->constants_text['PAYMENT_STATUS'] . " : " . $this->constants_text['PAYMENT_SUCCESS_STATUS']);
        }
    }

    /**
     * kfpwoo_set_order
     * Set order object
     * @param  mixed $order_id
     * @return bool
     */
    function kfpwoo_set_order($order_id)
    {
        $this->order = wc_get_order($order_id);
        if (!$this->order) {
            $current_session_id = sanitize_text_field($_COOKIE['KFPWOO_SESSION_ID']);
            $this->event_logging->kfpwoo_warning_logs($this->constants_text['POST_AUTH_LOGS'], $current_session_id, "wc_get_order returned false.  Skipping order_id: " . strval($order_id) . "  json:" . json_encode($this->order));
            return false;
        }
        return true;
    }

    /**
     * kfpwoo_get_product_details
     * Get product details from order object
     * @return array
     */
    function kfpwoo_get_product_details()
    {
        $shopping_cart_items  = [];
        $line_item_properties = [];
        $this->discount = 0;
        $pf = new WC_Product_Factory();
        foreach ($this->order->get_items() as $order_items => $order_item) {
            $order_item_meta = wc_get_order_item_meta($order_item->get_id(), '', false);
            foreach ($order_item_meta as $meta_key => $meta_value) {
                if ($meta_key != "_product_id" && $meta_key != "_variation_id" && $meta_key != "_qty") {
                    $line_items = (object)[
                        $this->constants_text['NAME']   => $meta_key,
                        $this->constants_text['VALUE']  => strval($meta_value[0])
                    ];
                    array_push($line_item_properties, $line_items);
                }
            }

            $product = $order_item->get_product();
            if ($product->get_sale_price() !== '') {
                $this->discount = $this->discount + ($product->get_regular_price() - $product->get_sale_price()) * $order_item->get_quantity();
            }

            $item = array(
                $this->constants_text['ID']           => $order_item->get_variation_id(),
                $this->constants_text['SKU']          => $product->get_sku(),
                $this->constants_text['PRODUCT_ID']   => strval($order_item->get_product_id()),
                $this->constants_text['NAME']         => $order_item->get_name(),
                $this->constants_text['DESCRIPTION']  => $product->get_description(),
                $this->constants_text['PRICE']        => strval($order_item->get_subtotal() / $order_item->get_quantity()),
                $this->constants_text['QUANTITY']     => $order_item->get_quantity(),
                $this->constants_text['IS_DIGITAL']   => $pf->get_product($order_item->get_product_id())->is_downloadable(),
                $this->constants_text['IS_GIFT_CARD'] => false,
                "lineItemProperties"                  => $line_item_properties
            );

            /***get product category */
            $product_cat_name = '';
            $terms = get_the_terms($order_item->get_product_id(), 'product_cat');
            if ($terms) {
                foreach ($terms  as $term) {

                    $product_cat_name = $product_cat_name . " " . $term->name;
                }
                $category = substr($product_cat_name, 0, 255);

                if ($category != "") {
                    $product_type = array(
                        $this->constants_text['PRODUCT_TYPE'] => $category
                    );
                    $product_details = array_merge($item, $product_type);
                    $item = $product_details;
                }
            }
            array_push($shopping_cart_items, $item);
        }
        return $shopping_cart_items;
    }

    /**
     * kfpwoo_get_coupon
     * Get coupon details
     * @return array
     */
    function kfpwoo_get_coupon()
    {
        $this->coupon_total = 0;
        $coupons = [];
        foreach ($this->order->get_coupons() as $code => $coupon) {
            $this->coupon_total++;
            $coupons_arr = (object)[
                $this->constants_text['NAME']     => $coupon->get_code(),
                $this->constants_text['AMOUNT']   => floatval($coupon->get_discount())
            ];
            array_push($coupons, $coupons_arr);
        }
        return $coupons;
    }

    /**
     * kfpwoo_get_order_properties
     * Get order items details
     * @return array
     */
    function kfpwoo_get_order_properties()
    {
        $order_properties = [];
        $exclude_details = ['_customer_user', '_payment_method', '_payment_method_title', '_customer_ip_address', '_customer_user_agent', '_billing_first_name', '_billing_last_name', '_billing_address_1', '_billing_address_2', '_billing_city', '_billing_state', '_billing_postcode', '_billing_country', '_billing_email', '_billing_phone', '_shipping_first_name', '_shipping_last_name', '_shipping_address_1', '_shipping_address_2', '_shipping_city', '_shipping_state', '_shipping_postcode', '_shipping_country', '_order_currency', '_order_total'];
        $order_meta = $this->order->get_meta('', false);
        foreach ($order_meta as $meta_key => $meta_value) {
            if (!in_array($meta_key, $exclude_details)) {
                $item = (object)[
                    $this->constants_text['NAME']       => $meta_key,
                    $this->constants_text['VALUE']      => strval($meta_value[0]),
                ];
                array_push($order_properties, $item);
            }
        }
        return $order_properties;
    }
    /**
     * kfpwoo_get_mapped_payment_type
     * Return payment type
     * @return string
     */
    function kfpwoo_get_mapped_payment_type($payment_type)
    {
        $payment_type = strtolower($payment_type);
        $payment_type = str_replace(' ', '', $payment_type);

        foreach ($this->payment_type as $key => $value) {
            if ($key == $payment_type) {
                return $value;
            }
        }
        $current_session_id = sanitize_text_field($_COOKIE['KFPWOO_SESSION_ID']);
        $this->event_logging->kfpwoo_error_logs($this->constants_text['POST_AUTH_LOGS'], $current_session_id, "Unknown payment type: " . $payment_type . ". Defaulting to NONE");
        return $this->constants_text['DEFAULT_PAYMENT_TYPE'];
    }

    /**
     * kfpwoo_create_json_payload
     * Create payload for order assessment ris api
     * @return string
     */
    function kfpwoo_create_json_payload()
    {
        /**get order details array */
        $order_details = $this->order->get_data();
        if (is_user_logged_in()) {
            $user_data = get_userdata($this->order->get_user_id());
            $customer_details = (object)[
                $this->constants_text['CUSTOMER_ID']      =>  $order_details['customer_id'],
                $this->constants_text['USER_NAME']        =>  $user_data->display_name,
                $this->constants_text['EMAIL_ADDRESS']    =>  $user_data->user_email,
                $this->constants_text['FIRST_NAME']       =>  $user_data->first_name,
                $this->constants_text['LAST_NAME']        =>  $user_data->last_name,
                $this->constants_text['ACCOUNT_CREATION_DATE']   => strval(strtotime($user_data->user_registered)),
            ];
        } else {
            $customer_details = (object)[
                $this->constants_text['EMAIL_ADDRESS']    => ($order_details['billing']['email'] != "" ? $order_details['billing']['email'] : $this->constants_text['NO_EMAIL_KOUNT']),
                $this->constants_text['FIRST_NAME']       => ($order_details['billing']['first_name'] != "" ? $order_details['billing']['first_name'] : 'GUEST'),
                $this->constants_text['LAST_NAME']        => ($order_details['billing']['last_name'] != "" ? $order_details['billing']['last_name'] : 'GUEST')
            ];
        }

        $shipping_method_id = "";
        $shipping_method    = "";
        foreach ($this->order->get_items('shipping') as $item_id => $item) {
            $shipping_method_id  =  $item->get_method_id();
            $shipping_method  = $item->get_method_title();
        }

        if ($this->payment_status == $this->constants_text['PAYMENT_SUCCESS_STATUS']) {
            $trans_status = 'A';
            $payment_type = $this->order->get_payment_method_title();
            $payment_type = $this->kfpwoo_get_mapped_payment_type($payment_type);
        } else if ($this->payment_status == $this->constants_text['PAYMENT_FAILED_STATUS']) {
            $trans_status = 'D';
            $payment_type = $this->order->get_payment_method_title();
            $payment_type = $this->kfpwoo_get_mapped_payment_type($payment_type);
        } else {
            $trans_status = 'E';
            $payment_type = $this->constants_text['DEFAULT_PAYMENT_TYPE'];
        }

        if ($order_details['total'] == 0) {
            $trans_status = 'A';
            $payment_type = $this->constants_text['DEFAULT_PAYMENT_TYPE'];
        }

        $current_session_id = sanitize_text_field($_COOKIE['KFPWOO_SESSION_ID']);
        $payload = (object)[
            $this->constants_text['K360_API_KEY']         =>  KFPWOO_Merchant_Settings::$k360_api_key,
            $this->constants_text['WOO_SITE']             =>  KFPWOO_Merchant_Settings::$payment_website,
            $this->constants_text['WOO_STORE_ID']         =>  KFPWOO_Merchant_Settings::$store_uuid,
            $this->constants_text['MODE']                 =>  $this->constants_text['MODE_NAME'],
            $this->constants_text['WOO_PLATFORM_VERSION'] =>  $order_details['version'],
            $this->constants_text['PLUGIN_VERSION']       =>  $this->constants_text['PLUGIN_VERSION_VALUE'],
            $this->constants_text['KOUNT_MERCHANT_ID']    =>  KFPWOO_Merchant_Settings::$merchant_id,
            $this->constants_text['SOURCE']               =>  (wp_is_mobile() == true) ? 'mob' : 'web',
            $this->constants_text['CART_ID']              =>  $current_session_id,
            $this->constants_text['BROWSER_IP']           =>  $_SERVER['REMOTE_ADDR'],
            $this->constants_text['ORDER_NUMBER']         =>  strval($order_details['id']),
            $this->constants_text['CUSTOMER_DETAILS']     =>  $customer_details,
            $this->constants_text['BILLING_DETAILS']      => (object)[
                $this->constants_text['LINE1']            =>  $order_details['billing']['address_1'],
                $this->constants_text['LINE2']            =>  $order_details['billing']['address_2'],
                $this->constants_text['CITY']             =>  $order_details['billing']['city'],
                $this->constants_text['STATE']            =>  $order_details['billing']['state'],
                $this->constants_text['POSTAL_CODE']      =>  $order_details['billing']['postcode'],
                $this->constants_text['COUNTRY_CODE']     =>  $order_details['billing']['country'],
                $this->constants_text['PHONE_NUMBER']     =>  $order_details['billing']['phone']
            ],
            $this->constants_text['SHIPPING_DETAILS']     => (object)[
                $this->constants_text['LINE1']            =>  $order_details['shipping']['address_1'],
                $this->constants_text['LINE2']            =>  $order_details['shipping']['address_2'],
                $this->constants_text['CITY']             =>  $order_details['shipping']['city'],
                $this->constants_text['STATE']            =>  $order_details['shipping']['state'],
                $this->constants_text['POSTAL_CODE']      =>  $order_details['shipping']['postcode'],
                $this->constants_text['COUNTRY_CODE']     =>  $order_details['shipping']['country'],
                $this->constants_text['FIRST_NAME']       =>  $order_details['shipping']['first_name'],
                $this->constants_text['LAST_NAME']        =>  $order_details['shipping']['last_name'],
                $this->constants_text['EMAIL_ADDRESS']    =>  isset($order_details['shipping']['shipping_email']) ? $order_details['shipping']['shipping_email'] : $order_details['billing']['email'],
                $this->constants_text['PHONE_NUMBER']     =>  isset($order_details['shipping']['shipping_phone']) ? $order_details['shipping']['shipping_phone'] : $order_details['billing']['phone'],
                $this->constants_text['SHIPPING_METHOD_ID']  =>  $shipping_method_id,
                $this->constants_text['SHIPPING_METHOD']  => $shipping_method
            ],
            $this->constants_text['SHOPPING_CART']        => $this->kfpwoo_get_product_details(),
            $this->constants_text['TRANSACTION_DETAILS']  => (object)[
                $this->constants_text['PAYMENT_TRANSACTION_ID']   => $order_details['transaction_id'],
                $this->constants_text['PAYMENT_METHOD']   =>  $this->order->get_payment_method(),
                $this->constants_text['PAYMENT_METHOD_TITLE']   =>  $this->order->get_payment_method_title(),
                $this->constants_text['TYPE']             => $this->constants_text['AUTHORIZATION'],
                $this->constants_text['PAYMENT_TYPE']     => $payment_type,
                $this->constants_text['PAYMENT_ENCRYPTION'] => '',
                $this->constants_text['TOKEN']            => '',
                $this->constants_text['BIN']              => '',
                $this->constants_text['LAST4']            => '',
                $this->constants_text['CURRENCY']         => $order_details['currency'],
                $this->constants_text['IS_TEST']          => true,
                $this->constants_text['TOTAL']            => strval($order_details['total']),
                $this->constants_text['DISCOUNT_TOTAL']   => floatval($this->discount),
                $this->constants_text['COUPON_TOTAL']     => floatval($this->coupon_total),
                $this->constants_text['COUPON_CODES']     => $this->kfpwoo_get_coupon(),
                $this->constants_text['STATUS']           => $trans_status,
                $this->constants_text['AVS_CODE']         => 'X',
                $this->constants_text['CVV_CODE']         => 'X',
            ],
            $this->constants_text['ORDER_PROPERTIES']     => $this->kfpwoo_get_order_properties(),
            $this->constants_text['ADMIN_CREATED'] => $this->order->is_created_via('admin'),
            $this->constants_text['CUSTOMER_IP'] => $this->order->get_customer_ip_address(),
            $this->constants_text['NEEDS_PROCESSING']     => $this->order->needs_processing(),
        ];
        if ( $this->order->is_created_via( 'admin' )) {
            $this->event_logging->kfpwoo_error_logs($this->constants_text['POST_AUTH_LOGS'], $current_session_id, "admin created order: browser_ip_addr: " . $_SERVER['REMOTE_ADDR'] . ", customer_ip_address: " . $this->order->get_customer_ip_address());
        }
        return wp_json_encode($payload);
    }

    /**
     * kfpwoo_http_request
     * Calling RIS API
     * @return void
     */
    function kfpwoo_http_request()
    {
        /**Payload */

        $payload = $this->kfpwoo_create_json_payload();
        /**RIS url */
        $ris_api = $this->site_config['RIS_URL'] . $this->site_config['RIS_ENDPOINT'];
        /**Request parameter */
        $request        = [
            $this->constants_text["API_URL"]       =>  $ris_api,
            $this->constants_text["API_KEY"]       =>  "Bearer " . KFPWOO_Merchant_Settings::$api_key,
            $this->constants_text["PAYLOAD"]       =>  $payload,
            $this->constants_text["METHOD"]        =>  "POST",
            $this->constants_text["HEADER"]        =>  "Authorization",
            $this->constants_text["CALL_FORM"]     =>  "RIS",
        ];

        $response = $this->call_api_obj->kfpwoo_call_api($request);
        $current_session_id = sanitize_text_field($_COOKIE['KFPWOO_SESSION_ID']);
        /** handling response */
        if (!$response) {
            $this->event_logging->kfpwoo_error_logs($this->constants_text['POST_AUTH_LOGS'], $current_session_id, $this->constants_text['POST_AUTH_FAILED']);
        } else {
            $this->event_logging->kfpwoo_info_logs($this->constants_text['POST_AUTH_LOGS'], $current_session_id, $this->constants_text['POST_AUTH_SUCCESS'] . " response: " . json_encode($response));
            $uo = [
                'ORDR'             => $this->order->get_id(),
                'SESS'             => $current_session_id,
                'AUTO'             => $response->decision,
                'TRAN'             => $response->transactionID,           
                'SCOR'             => $response->SCOR,      
                'OMNISCORE'        => $response->OMNISCORE,     
                'REASON_CODE'      => $response->REASON_CODE,       
                'RULES_TRIGGERED'  => $response->RULES_TRIGGERED,       
                'GEOX'             => $response->GEOX,      
                'KAPT'             => $response->KAPT,      
                'CARDS'            => $response->CARDS,     
                'EMAILS'           => $response->EMAILS,        
                'DEVICES'          => $response->DEVICES,       
            ];
            KFPWOO_Response_API::kfpwoo_update_order($uo);
        }

        $this->event_logging->kfpwoo_info_logs($this->constants_text['POST_AUTH_LOGS'], $_COOKIE['KFPWOO_SESSION_ID'], 'unsetting the cookie');
        unset($_COOKIE['KFPWOO_SESSION_ID']);
        setcookie('KFPWOO_SESSION_ID', '', -1, '/'); 
    }
}
