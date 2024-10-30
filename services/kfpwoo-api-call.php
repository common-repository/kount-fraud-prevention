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
 * API Call
 *
 * This is used for calling api
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/KFPWOO_Call_API
 * @author     Kount Inc. <developer@kount.com>
 *
 */

class KFPWOO_Call_API {

    /**public variables */
    public $event_logging, $constants_text;

    /**
     * __construct
     * It will all automatically when the call load
     * @return void
     */
    public function __construct() {
        //logs class object (responsible for logs)
        $this->event_logging            =     new KFPWOO_Event_logging();
        //constants class object (responsible for static text)
        $constants_obj                  =     new KFPWOO_Constants_();
        $this->constants_text           =     $constants_obj->kfpwoo_constants_text();
    }
    /**
     * kfpwoo_call_api
     *
     * @param  mixed $request
     * @return mixed
     */
    public function kfpwoo_call_api($request){
        $current_session_id = sanitize_text_field($_COOKIE['KFPWOO_SESSION_ID']);
        //check for empty request parameter
        if(!empty($request)){
            if($request["call_from"] && $request["call_from"]=="RIS"){
                //header for ris request call
                $request_headers = array(
                    $request["header"]=> $request["api_key"], //Payment API key
                    'X-Kount-Hash'    => KFPWOO_Merchant_Settings::$x_kount_hash, 
                    'Content-Type'    =>'application/json'
                );
            }
            $args = array(
                'body'        => $request["payload"],
                'method'      => $request["method"],
                'timeout'     => '35',
                'redirection' => '35',
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => $request_headers,
                'cookies'     => array(),
            );

            $this->event_logging->kfpwoo_info_logs($this->constants_text['API_LOGS'],$current_session_id,$this->constants_text['LOG_API_URL']." : ".$request["api_url"]);
            $this->event_logging->kfpwoo_debug_logs($this->constants_text['API_LOGS'],$current_session_id,$this->constants_text['LOG_METHOD']." : ".$request["method"]);

            /** handling response */
            try{
                for ($i=0; $i<5; $i++){
                    $response    = wp_remote_request($request["api_url"], $args);
                    if (is_wp_error($response)) {
                        $this->event_logging->kfpwoo_error_logs($this->constants_text['API_LOGS'], $current_session_id, $this->constants_text['API_RESPONSE'] . $this->constants_text['UNKNOWN_ERROR_OCCURED'] . " : WP_Error" . $response->get_error_message());
                        return $this->kfpwoo_default_response($request["call_from"]);
                    }
                    $response_   = json_decode(wp_remote_retrieve_body($response));
                    $response_body = wp_remote_retrieve_body($response);
                    $httpcode    = wp_remote_retrieve_response_code($response);
                    switch ($httpcode) {
                        case 200:
                            $this->event_logging->kfpwoo_info_logs($this->constants_text['API_LOGS'], $current_session_id, "Httpcode :".$httpcode);
                            $this->event_logging->kfpwoo_debug_logs($this->constants_text['API_LOGS'], $current_session_id, $this->constants_text['API_RESPONSE']." : ".$response_body.", Httpcode :".$httpcode);
                            return $response_;
                        case 409:
                            $this->event_logging->kfpwoo_error_logs($this->constants_text['API_LOGS'], $current_session_id, "Request to ".$request["api_url"]." failed");
                            $this->event_logging->kfpwoo_error_logs($this->constants_text['API_LOGS'], $current_session_id, $this->constants_text['API_RESPONSE']." : ".$response_body.", Httpcode :".$httpcode);
                            return $response_;
                        case 400:
                        case 404:
                        case 500:
                            $this->event_logging->kfpwoo_error_logs($this->constants_text['API_LOGS'], $current_session_id, "Request to ".$request["api_url"]." failed on try " . $i);
                            $this->event_logging->kfpwoo_error_logs($this->constants_text['API_LOGS'], $current_session_id, $this->constants_text['API_RESPONSE']." : ".$response_body.", Httpcode :".$httpcode);
                        default:
                            $this->event_logging->kfpwoo_error_logs($this->constants_text['API_LOGS'], $current_session_id, "Request to ".$request["api_url"]." failed on try " . $i);
                            $this->event_logging->kfpwoo_error_logs($this->constants_text['API_LOGS'], $current_session_id, $this->constants_text['API_RESPONSE'].$this->constants_text['UNKNOWN_ERROR_OCCURED']." : ".$response_body.", Httpcode :".$httpcode);               
                    }
                    usleep(200000); // wait 0.2 seconds before a retry
                }
                return $this->kfpwoo_default_response($request["call_from"]);
            }
            catch(Exception $e){
                $this->event_logging->kfpwoo_error_logs($this->constants_text['API_LOGS'], $current_session_id, "Request to ".$request["api_url"]." failed");
                $this->event_logging->kfpwoo_error_logs($this->constants_text['API_LOGS'], $current_session_id, $e->getMessage());
                return $this->kfpwoo_default_response($request["call_from"]);
            }
        }
        /**If request parameter is missing */
        else {
            $this->event_logging->kfpwoo_error_logs($this->constants_text['API_LOGS'], $current_session_id, $this->constants_text['MISSING_REQUIRED_REQUEST_FILEDS']);
        }

    }

    /**
     * kfpwoo_default_response
     * This function is responsible for return default response if there is no response or any error retrieved from api
     * @param  mixed $call_from
     * @return string
     */
    public function kfpwoo_default_response($call_from){
        //checking variable availability
        if($call_from) {
            /**Default response for ris request */
            if($call_from == "RIS"){
                $kfpwoo_default_response = (object)[
                    $this->constants_text['DECISION']          =>  $this->constants_text['DEFAULT_RIS_DECISION'],
                    $this->constants_text["TRANSACTION_ID"]    =>  ""
                ];
            }
            /**response return */
            return wp_json_encode($kfpwoo_default_response);
        }
        return "";
    }
}
