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
 * KFPWOO_Account_Creation
 *
 * This class is used to call the account creation risk inquiry and send data to kount server
 *
 * @since       1.0.0
 * @package     Kount
 * @subpackage  Kount/Services
 * @author      Kount Inc. <developer@kount.com>
 *
 */

require_once 'kfpwoo-api-call.php';

class KFPWOO_Account_Creation
{
    /**public variable */
    public $kount_config, $constants_text, $event_logging,
    $call_api_obj, $current_session_id, $user_obj;

    /**
     * __construct
     * It will call automatically when class object created
     * @return void
     */
    public function __construct(){
        //configuration urls
        $config                  =  new KFPWOO_Config_;
        $this->kount_config      =  $config->config_();
        //constants class object (responsible for static text)
        $costants_obj            =  new KFPWOO_Constants_();
        $this->constants_text    =  $costants_obj->kfpwoo_constants_text();
        //logs class object (responsible for logs)
        $this->event_logging     =  new KFPWOO_Event_logging();
        //Http Call
        $this->call_api_obj      =  new KFPWOO_Call_API();
        //session id
        $this->current_session_id=  sanitize_text_field($_SESSION['KFPWOO_SESSION_ID']);
        // properties
    }

    /**
     * kfpwoo_account_creation_ris_inquiry
     *
     * @param  mixed $user_id
     * @return void
     */
    public function kfpwoo_account_creation_ris_inquiry($user_id){
        /**user data */
        $this->user_obj         =  get_userdata($user_id);
        /**payload */
        $payload                =  $this->kfpwoo_payload_account_creation($user_id);
        /**Http call */
        $response               =  $this->kfpwoo_api_call($payload);
        $auto                   =  $response->decision; //kount account creation status
        $user_name              =  $this->user_obj->user_login; //user name form user data
        $naoTran                =  $response->transactionID;
        $this->kfpwoo_update_and_log_meta_field($user_id, "nao_tran", $naoTran);
        /** update kount account status status to user meta */
        $this->kfpwoo_update_meta_field($user_id, $auto);
        /** add kount account creation status to user list in admin */
        $this->kfpwoo_add_account_creation_status($user_id, $auto);
        /**call event according to kount account creation status */
        if ($auto == $this->constants_text['DECLINE_RESPONSE'])
            $this->kfpwoo_unset_k_session_if_checkout();
    }

    /**
     * kfpwoo_unset_k_session_if_checkout
     * call when kount returns decline for account creation
     * @return void
     */
    public function kfpwoo_unset_k_session_if_checkout()
    {
        do_action('woocommerce_set_cart_cookies', true);
        // unset a session 
        if (!is_checkout()) {
            //get my account page permalink
            $my_account_page = get_permalink(get_option('woocommerce_myaccount_page_id'));
            //redirect to register page
            wp_safe_redirect($my_account_page);
            unset($_SESSION['KFPWOO_SESSION_ID']);
            //exit the process
            exit();
        }
    }

    /**
     * kfpwoo_payload_account_creation
     * payload for customer assessment
     * @param  mixed $user_id
     * @return string
     */
    public function kfpwoo_payload_account_creation($user_id) {
        //creating payload
        $name = explode(" ", $this->user_obj->user_nicename, 2);
        $payload = (object)[
            $this->constants_text['WOO_SITE']               =>  KFPWOO_Merchant_Settings::$payment_website,
            $this->constants_text['KOUNT_MERCHANT_ID']      =>  KFPWOO_Merchant_Settings::$merchant_id,
            $this->constants_text['PLUGIN_VERSION']         =>  $this->constants_text['PLUGIN_VERSION_VALUE'],
            $this->constants_text['KFPWOO_SESSION_ID']       =>  $this->current_session_id,
            $this->constants_text['BROWSER_IP']             =>  $_SERVER['REMOTE_ADDR'],
            $this->constants_text['CUSTOMER_DETAILS']       => (object)[
                $this->constants_text['CUSTOMER_ID']        =>  $this->user_obj->ID,
                $this->constants_text['USER_NAME']          =>  $this->user_obj->user_login,
                $this->constants_text['EMAIL_ADDRESS']      =>  $this->user_obj->user_email,
                $this->constants_text['FIRST_NAME']         =>  array_key_exists(0, $name) ? $name[0] : '',
                $this->constants_text['LAST_NAME']          =>  array_key_exists(1, $name) ? $name[1] : '',
                $this->constants_text['ACCOUNT_CREATION_DATE']   =>     strval(strtotime($this->user_obj->user_registered))
            ],
            $this->constants_text['BILLING_DETAILS']        => (object)[
                $this->constants_text['LINE1']              =>  '',
                $this->constants_text['LINE2']              =>  '',
                $this->constants_text['CITY']               =>  '',
                $this->constants_text['STATE']              =>  '',
                $this->constants_text['POSTAL_CODE']        =>  '',
                $this->constants_text['COUNTRY_CODE']       =>  '',
                $this->constants_text['PHONE_NUMBER']       =>  '',
            ],
        ];
        //return payload
        return wp_json_encode($payload);
    }

    /**
     * kfpwoo_api_call
     * Send account creation request to kount
     * @param  mixed $payload
     * @return object
     */
    public function kfpwoo_api_call($payload) {
        /**customer assessment api url */
        $ris_api = $this->kount_config['RIS_URL'] . $this->kount_config['CUSTOMER_ASSESSMENT'];
        /**Request parameter */
        $request        = [
            $this->constants_text["API_URL"]       =>  $ris_api,
            $this->constants_text["API_KEY"]       =>  "Bearer ".KFPWOO_Merchant_Settings::$api_key,
            $this->constants_text["PAYLOAD"]       =>  $payload,
            $this->constants_text["METHOD"]        =>  "POST",
            $this->constants_text["HEADER"]        =>  "Authorization",
            $this->constants_text["CALL_FORM"]     =>  "RIS",
        ];
        /**Http call */
        $response=$this->call_api_obj->kfpwoo_call_api($request);
        /**return response */
        return $response;
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
        $updateResult = update_user_meta($user_id, $meta_key, $meta_value);

        $this->event_logging->kfpwoo_info_logs(
            $this->constants_text['ACCOUNT_CREATION_LOGS'],
            $this->current_session_id,
            "called update_user_meta(" . $user_id . ", " . $meta_key . ", " . $meta_value . ") result: " . $updateResult
        );
    }

    /**
     * kfpwoo_update_meta_field
     * Responsible for updating meta field value
     * @param  mixed $user_id
     * @param  mixed $meta_value
     * @return void
     */
    public function kfpwoo_update_meta_field($user_id, $meta_value){
        $meta_key= "kount_account_creation_status";
        update_user_meta( $user_id, $meta_key, $meta_value);
        $this->event_logging->kfpwoo_info_logs($this->constants_text['ACCOUNT_CREATION_LOGS'],$this->current_session_id,$this->constants_text['ACCOUNT_CREATION_STATUS_UPDATED']." : ".$meta_value);

    } 

    /**
     * kfpwoo_add_account_creation_status
     * Responsible for adding account creation status to userlist
     * @param  mixed $user_id
     * @param  mixed $auto
     * @return void
     */
    public function kfpwoo_add_account_creation_status($user_id, $auto){
        $meta_key= $this->constants_text['KOUNT_ACCOUNT_STATUS'];
        update_user_meta( $user_id, $meta_key, $auto);
    }
}
