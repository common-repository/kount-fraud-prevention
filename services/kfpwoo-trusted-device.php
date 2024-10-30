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
 * Trusted Device
 *
 * This is used to add trusted device for login and account creation
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/TrustedDevice
 * @author     Kount Inc. <developer@kount.com>
 *
 */
require_once plugin_dir_path( dirname( __FILE__ ) )."services/kfpwoo-api-call.php";
class KFPWOO_Trusted_Device {
    /***public static variables*/
    public static $kount_config, $event_logging, $constants_text;

    /**
     * kfpwoo_submit_trusted_device
     * calling API for creating trusted device
     * @param  mixed $trusted_device_details
     * @return string
     */
    public static function kfpwoo_submit_trusted_device($trusted_device_details) {
        @session_start();
        //configuration urls
        $config                     =   new KFPWOO_Config_();
        self::$kount_config         =   $config->config_();
        //logs class object (responsible for logs)
        self::$event_logging        =   new KFPWOO_Event_logging();
        //constants class object (responsible for static text)
        $costants_obj               =   new KFPWOO_Constants_();
        self::$constants_text       =   $costants_obj->kfpwoo_constants_text();

        $login_trusted_device_enabled = KFPWOO_Merchant_Settings::$is_trusted_device_login_allow;
        $trusted_device_enabled = KFPWOO_Merchant_Settings::$is_trusted_device_enable;
        //check if trusted enable for login or account creation
            //check for $trusted_device_details
            if(!empty($trusted_device_details)){
                /***Payload for add trusted device */
                $merchant_id      =   KFPWOO_Merchant_Settings::$merchant_id;
                $user_id          =   isset($trusted_device_details['user_id'])?sanitize_text_field($trusted_device_details['user_id']):'';
                $session_id       =   isset($trusted_device_details['session_id'])?sanitize_text_field($trusted_device_details['session_id']):'';
                $trust_status     =   isset($trusted_device_details['trust_status'])?sanitize_text_field($trusted_device_details['trust_status']):'';
                $friendly_name    =   isset($trusted_device_details['friendly_name'])?sanitize_text_field($trusted_device_details['friendly_name']):'';
                $created_via      =   isset($trusted_device_details['via'])?sanitize_text_field($trusted_device_details['via']):'';
                if(($login_trusted_device_enabled && $created_via == "login") || ($trusted_device_enabled && $created_via == "account_creation")){
                    $payload=(object)[
                        self::$constants_text["CLIENT_ID"]      =>  strval($merchant_id),
                        self::$constants_text["SESSION_ID"]     =>  strval($session_id),
                        self::$constants_text["USER_ID"]        =>  strval($user_id),
                        self::$constants_text["TRUST_STATE"]    =>  strval($trust_status),
                        self::$constants_text["FRIENDLY_NAME"]  =>  strval($friendly_name)
                    ];
                    $payload = wp_json_encode($payload);

                    /**API key for authorization */
                    $api_key=KFPWOO_Merchant_Settings::$login_api_key;

                    /**API url for authorization */
                    $api_url= self::$kount_config['LOGIN_URL'] . self::$kount_config['TRUSTED_DEVICE'];

                    /**Request parameter */
                    $request        = [
                        self::$constants_text["API_URL"]       =>  $api_url,
                        self::$constants_text["API_KEY"]       =>  "Bearer ".$api_key,
                        self::$constants_text["PAYLOAD"]       =>  $payload,
                        self::$constants_text["METHOD"]        =>  "POST",
                        self::$constants_text["HEADER"]        =>  "Authorization",
                        self::$constants_text["CALL_FORM"]     =>  "",
                    ];
                    $call_api_obj=new KFPWOO_Call_API();
                    $response=$call_api_obj->kfpwoo_call_api($request);
                    if(!is_checkout()) {
                        unset($_SESSION['KFPWOO_SESSION_ID']);
                    }
                    /**handle response and event logging*/
                    if($response){
                        //trusted device added
                        self::$event_logging->kfpwoo_info_logs(self::$constants_text['TRUSTED_DEVICE_LOGS'],$session_id,self::$constants_text['TRUSTED_ADDED']);
                        $response_array=array("message"=>self::$constants_text['TRUSTED_ADDED'],"status"=>true);
                        return wp_json_encode($response_array, true);
                    }
                    else{
                        //trusted device not added
                        self::$event_logging->kfpwoo_error_logs(self::$constants_text['TRUSTED_DEVICE_LOGS'],$session_id,self::$constants_text['TRUSTED_NOT_ADDED']);
                        $response_array=array("message"=>self::$constants_text['TRUSTED_NOT_ADDED'],"status"=>false);
                        return wp_json_encode($response_array, true);
                    }
                }else{
                        //return error if trusted device toggle is off
                        self::$event_logging->kfpwoo_error_logs(self::$constants_text['TRUSTED_DEVICE_LOGS'],$session_id,self::$constants_text['TRUSTED_OFF']);
                        $response=array("message"=>self::$constants_text['TRUSTED_OFF'],"status"=>false);
                        if(!is_checkout()) {
                            unset($_SESSION['KFPWOO_SESSION_ID']);
                        }
                        return wp_json_encode($response);
                    }
            }
            else{
                //return error if $trusted_device_details is empty
                self::$event_logging->kfpwoo_error_logs(self::$constants_text['TRUSTED_NOT_ADDED'],'',self::$constants_text['STEPUP_REQUEST_PARAMETER']);
                $response=array("message"=>self::$constants_text['STEPUP_REQUEST_PARAMETER'],"status"=>false);
                if(!is_checkout()) {
                    unset($_SESSION['KFPWOO_SESSION_ID']);
                }
                return wp_json_encode($response);
            }
        }

}
