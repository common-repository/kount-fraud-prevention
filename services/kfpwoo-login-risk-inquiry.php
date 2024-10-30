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
 * KFPWOO_Login_Risk_Inquiry
 *
 * This is used to class to authenticate woocommerce user from kount side
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/LoginRiskInquiry
 * @author     Kount Inc. <developer@kount.com>
 *
 */
require_once plugin_dir_path( dirname( __FILE__ ) )."services/kfpwoo-api-call.php";

class KFPWOO_Login_Risk_Inquiry {

    /***public variables*/
    public $current_session_id,
    $merchant_id,
    $loginUrl,
    $event_logging,
    $call_api_obj,
    $constants_text,
    $kount_config,
    $api_key;
    /**public static variable */
    public static $login_session_id;

    /**
     * __construct
     * It will call automatically when class object created
     * @return void
     */
    public function __construct(){
        //current session id
        $this->current_session_id       =     sanitize_text_field($_SESSION['KFPWOO_SESSION_ID']);
        //Merchant id
        $this->merchant_id              =     KFPWOO_Merchant_Settings::$merchant_id;
        //myaccount page url by page id
        $this->loginUrl                 =     get_permalink( get_option('woocommerce_myaccount_page_id') );
        //constants class object (responsible for static text)
        $costants_obj                   =     new KFPWOO_Constants_();
        $this->constants_text           =     $costants_obj->kfpwoo_constants_text();
        //configuration urls
        $config                         =     new KFPWOO_Config_();
        $this->kount_config             =     $config->config_();
        //logs class object (responsible for logs)
        $this->event_logging            =     new KFPWOO_Event_logging();
        //API call (responsible for http request)
        $this->call_api_obj             =     new KFPWOO_Call_API();
        //login API key
        $this->api_key                  =     KFPWOO_Merchant_Settings::$login_api_key;

    }

    /**
     * kfpwoo_get_user_ip_addr
     * return user ip address
     * @return string
     */
    public function kfpwoo_get_user_ip_addr(){
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            //ip from share internet
            $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            //ip pass from proxy
            $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        }else{
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return $ip;
    }


    /**
     * kfpwoo_get_payload
     * Payload for login http request call
     * @param  mixed $user_obj
     * @return string
     */
    public function kfpwoo_get_payload($user_obj){
        /**converting datetime into ISO format */
        $dt                 =   new DateTime($user_obj->user_registered);
        $creation_dt        =   $dt->format('Y-m-d\TH:i:s.').substr($dt->format('u'),0,3).'Z';
        /**Kount account creation status */
        $kount_account_status       =   get_user_meta( $user_obj->ID, 'kount_account_status',true );
        /**Creating payload for login API */
        $userId                     =    $user_obj->ID ? trim($user_obj->ID):'';
        $userAuthenticationStatus   =    $user_obj->user_status ?trim($user_obj->user_status):'';
        $username                   =    $user_obj->user_login? trim($user_obj->user_login):'';
        $userPassword               =    $user_obj->user_pass? trim($user_obj->user_pass):'';
        $userCreationDate           =    $user_obj->user_registered? trim($creation_dt) :'';
        $userIp                     =    $this->kfpwoo_get_user_ip_addr();
        $userType                   =    ($kount_account_status == 'D')?'Block':($user_obj->roles[0]? trim($user_obj->roles[0]):'');
        $payload=(object)[
            $this->constants_text["CLIENT_ID"]          =>  strval($this->merchant_id), //Client ID
            $this->constants_text["LOGIN_URL"]          =>  strval($this->loginUrl), //login url
            $this->constants_text["SESSION_ID"]         =>  strval($this->current_session_id), //session id
            $this->constants_text["USER_ID"]            =>  strval($userId), //user id
            $this->constants_text["AUTH_STATUS"]        =>  "true", //auth status
            $this->constants_text["CUSTOMER_USER"]      =>  strval($username), //username
            $this->constants_text["CUSTOMER_PASS"]      =>  strval($userPassword), //password
            $this->constants_text["CREATION_DATE"]      =>  strval($userCreationDate), //user creation date
            $this->constants_text["USER_IP"]            =>  strval($userIp), //user ip
            $this->constants_text["USER_TYPE"]          =>  strval($userType) //user type
        ];
        return wp_json_encode($payload);

    }

    /**
     * kfpwoo_login_user_auth
     * Http request call
     * @param  mixed $user
     * @return void
     */
    public function kfpwoo_login_user_auth($user){
        session_start();
        //check if user authenticated or not
        if($user){
            /**User data object */
            $user_obj       = get_user_by('login', $user );
            $userId         = $user_obj->ID ? trim($user_obj->ID):'';

            /**Payload for login API */
            $payload        = $this->kfpwoo_get_payload($user_obj);

            /**API url for authorization */
            $api_url        = $this->kount_config['LOGIN_URL'] . $this->kount_config['LOGIN_ENDPOINT'];

            /**Request parameter */
            $request        = [
                $this->constants_text["API_URL"]       =>  $api_url, //API url
                $this->constants_text["API_KEY"]       =>  "Bearer ".$this->api_key, //Authorization header
                $this->constants_text["PAYLOAD"]       =>  $payload, //payload
                $this->constants_text["METHOD"]        =>  "POST", //http method
                $this->constants_text["HEADER"]        =>  "Authorization", //header type
                $this->constants_text["CALL_FORM"]     =>  "login", //call form
            ];

            /***Http call */
            $response=$this->call_api_obj->kfpwoo_call_api($request);

            /** handling response */
            if($response){
                //logs
                $this->event_logging->kfpwoo_info_logs($this->constants_text['LOGIN_LOGS'],$this->current_session_id,$this->constants_text['KOUNT_LOGIN_DICISION']." : ".wp_json_encode($response));
                //user meta key for login status
                $meta_key = $this->constants_text['KOUNT_LOGIN_STATUS'];
                //meta value for login status
                $meta_value = $response->decision;
                // $meta_value ='Decline';
                //add meta field or update if exists
                update_user_meta( $userId, $meta_key, $meta_value);
                //if kount decline a user login
                if($meta_value == "Block"){
                    $this->kfpwoo_logout_redirect_user();
                }
                else if($meta_value == "Allow"){
                    unset($_SESSION['KFPWOO_SESSION_ID']);
                }

            }
            else{
                $this->event_logging->kfpwoo_error_logs($this->constants_text['LOGIN_LOGS'],$this->current_session_id,$this->constants_text['KOUNT_LOGIN_RESPONSE_FAILED']);
                unset($_SESSION['KFPWOO_SESSION_ID']);
            }
        }
        else{
            //event logs if user not authenticated
            $this->event_logging->kfpwoo_error_logs($this->constants_text['LOGIN_LOGS'],$this->current_session_id,$this->constants_text['USER_NOT_AUTHENTICATE']);
            unset($_SESSION['KFPWOO_SESSION_ID']);
        }
    }


    /**
     * kfpwoo_logout_redirect_user
     * logout user if kount decline a user login
     * @return void
     */
    public function kfpwoo_logout_redirect_user(){
        do_action('woocommerce_set_cart_cookies', true);
        //myaccount page permalink
        $my_account_page  = get_permalink(get_option('woocommerce_myaccount_page_id'));
        //destroy session
        wp_destroy_current_session();
        wp_clear_auth_cookie();
        wp_set_current_user( 0 );
        //redirecting user and adding notes
        session_start();
        unset($_SESSION['KFPWOO_SESSION_ID']);
        wp_safe_redirect($my_account_page);
        wc_add_notice(__("Unable to login.", 'kount-fraud-prevention'), "error");
        exit();
    }

    /**
     * kfpwoo_user_id_exists
     * This function is responsible for checking user existence
     * @param  mixed $user_id
     * @return bool
     */
    public static function kfpwoo_user_id_exists($user_id){
        //global wp db variable
        global $wpdb;
        /**query for checking user availability */
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $user_id));
        /**If user found the return true else false */
        if($count == 1){ return true; } else { return false; }

    }

    /**
     * kfpwoo_get_login_status
     * This function is responsible for fetching kount login status
     * @param  mixed $user_ID
     * @return string
     */
    public static function kfpwoo_get_login_status($user_ID){
        //check if user exists or not
        $is_user_exists         = self::kfpwoo_user_id_exists($user_ID);
        //if exists then return details
        if($is_user_exists){
            $status             =   get_user_meta( $user_ID, 'kount_login_status',true ); //kount login status
            $login_array        =   (object)[
                "Status"        =>  $status,
                "Session_ID"    =>  sanitize_text_field($_SESSION['KFPWOO_SESSION_ID'])
            ];
        }
        //else return error
        else{
            $login_array        =   (object)[
                "error"         =>  true,
                "message"       =>  "User Id not valid."
            ];
        }
        return wp_json_encode($login_array);
      }

    /**
     * kfpwoo_get_failed_login_payload
     * creates payload for login failed
     * @param  mixed $username
     * @param  mixed $password
     * @return string
     */
    public function kfpwoo_get_failed_login_payload($username,$password){
        //payload for failed login
        $payload = (object)[
            $this->constants_text["FAILED_ATTEMPT"]  => (object)[
            $this->constants_text["CLIENT_ID"]       => strval($this->merchant_id),
            $this->constants_text["SESSION_ID"]      => strval($this->current_session_id),
            $this->constants_text["USER_ID"]         => "",
            $this->constants_text["CUSTOMER_USER"]   => strval($username),
            $this->constants_text["CUSTOMER_PASS"]   => strval(md5($password)),
            $this->constants_text["USER_IP"]         => strval($this->kfpwoo_get_user_ip_addr()),
            $this->constants_text["LOGIN_URL"]       => strval($this->loginUrl)
            ]
        ];
        //return payload
        return wp_json_encode($payload);
    }

    /**
     * kfpwoo_failed_login_attempt
     * It will capture failed login attempts and send them to kount
     * @param  mixed $username
     * @param  mixed $password
     * @return void
     */
    public function kfpwoo_failed_login_attempt($username, $password){
        /**Payload  */
        $payload    =   $this->kfpwoo_get_failed_login_payload($username, $password);

        /**Event api key */
        $event_api_url = $this->kount_config['LOGIN_URL'] . $this->kount_config['EVENTS_ENDPOINT'];

        /**Request parameter */
        $request=[
            $this->constants_text["API_URL"]       =>  $event_api_url,
            $this->constants_text["API_KEY"]       =>  "Bearer ".$this->api_key,
            $this->constants_text["PAYLOAD"]       =>  $payload,
            $this->constants_text["METHOD"]        =>  "POST",
            $this->constants_text["HEADER"]        =>  "Authorization",
            $this->constants_text["CALL_FORM"]     =>  "",
        ];

        /***Http call */
        $response=$this->call_api_obj->kfpwoo_call_api($request);

        /***handle response */
        if($response){
            $this->event_logging->kfpwoo_info_logs($this->constants_text['LOGIN_LOGS'],$this->current_session_id,$this->constants_text['LOGIN_FAILED_RESPONSE']." : Success");
        }
        else{
            $this->event_logging->kfpwoo_error_logs($this->constants_text['LOGIN_LOGS'],$this->current_session_id,$this->constants_text['LOGIN_FAILED_RESPONSE']." : Failed");
        }
        session_start();
        unset($_SESSION['KFPWOO_SESSION_ID']);
    }

}
