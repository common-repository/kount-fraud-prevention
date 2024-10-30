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
 * Stepup Challenge
 *
 * This is used when kount login status is challenge and MFA implemented
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/StepupChallenge
 * @author     Kount Inc. <developer@kount.com>
 *
 */
require_once plugin_dir_path(dirname(__FILE__)) . "services/kfpwoo-api-call.php";

class KFPWOO_Stepup_Challenge
{

  /***public static variables*/
  public static $kount_config, $event_logging, $constants_text;

  /**
   * kfpwoo_correlation_id
   * creating correlation id for woocommerce endpoints
   * @return string
   */
  public static function kfpwoo_correlation_id()
  {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

      // 32 bits for "time_low"
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),

      // 16 bits for "time_mid"
      mt_rand(0, 0xffff),

      // four most significant bits holds version number 4
      mt_rand(0, 0x0fff) | 0x4000,

      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand(0, 0x3fff) | 0x8000,

      // 48 bits for "node"
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
  }

  /**
   * kfpwoo_login_challenge_outcome
   * It will send MFA details to kount after MFA completed or failed
   * @param  mixed $mfa_details
   * @return string
   */
  public static function kfpwoo_login_challenge_outcome($mfa_details)
  {

    //configuration urls
    $config = new KFPWOO_Config_;
    self::$kount_config = $config->config_();
    //logs class object (responsible for logs)
    self::$event_logging = new KFPWOO_Event_logging();
    //constants class object (responsible for static text)
    $costants_obj = new KFPWOO_Constants_();
    self::$constants_text = $costants_obj->kfpwoo_constants_text();

    //check if login toggle is enabled
    $login_enabled = KFPWOO_Merchant_Settings::$is_login_enable;
    if ($login_enabled) {
      //Check $mfa_details
      if (!empty($mfa_details)) {
        /***Payload for loginchallengeoutcome */
        $merchant_id = KFPWOO_Merchant_Settings::$merchant_id;
        $correlation_id = self::kfpwoo_correlation_id();
        $user_id = isset($mfa_details['user_id']) ? sanitize_text_field($mfa_details['user_id']) : '';
        $session_id = isset($mfa_details['session_id']) ? sanitize_text_field($mfa_details['session_id']) : '';
        $challenge_type = isset($mfa_details['challenge_type']) ? sanitize_text_field($mfa_details['challenge_type']) : '';
        $challenge_status = isset($mfa_details['challenge_status']) ? sanitize_text_field($mfa_details['challenge_status']) : '';
        $sent_timestamp = isset($mfa_details['challenge_sent_timestamp']) ? sanitize_text_field($mfa_details['challenge_sent_timestamp']) : '';
        $completed_timestamp = isset($mfa_details['challenge_completed_timestamp']) ? sanitize_text_field($mfa_details['challenge_completed_timestamp']) : '';
        $failure_type = isset($mfa_details['challenge_failure_type']) ? sanitize_text_field($mfa_details['challenge_failure_type']) : '';
        $payload = (object) [
          self::$constants_text["CHALLENGE_OUTCOME"] => (object) [
            self::$constants_text["CLIENT_ID"] => strval($merchant_id),
            self::$constants_text["LOGIN_CORREL_ID"] => strval($correlation_id),
            self::$constants_text["CHALLENGE_TYPE"] => strval($challenge_type),
            self::$constants_text["CHALLENGE_STATUS"] => strval($challenge_status),
            self::$constants_text["SESSION_ID"] => strval($session_id),
            self::$constants_text["USER_ID"] => strval($user_id),
            self::$constants_text["SENT_TIMESTAMP"] => strval($sent_timestamp),
            self::$constants_text["COMPLETED_TIMESTAMP"] => strval($completed_timestamp),
            self::$constants_text["FAILURE_TYPE"] => strval($failure_type)
          ]
        ];
        $payload = wp_json_encode($payload);

        /**API key for authorization */
        $api_key = KFPWOO_Merchant_Settings::$login_api_key;

        /**API url for authorization */
        $api_url = self::$kount_config['LOGIN_URL'] . self::$kount_config['EVENTS_ENDPOINT'];

        /**Request parameter */
        $request = [
          self::$constants_text["API_URL"] => $api_url,
          self::$constants_text["API_KEY"] => "Bearer " . $api_key,
          self::$constants_text["PAYLOAD"] => $payload,
          self::$constants_text["METHOD"] => "POST",
          self::$constants_text["HEADER"] => "Authorization",
          self::$constants_text["CALL_FORM"] => "",
        ];
        /***Http call */
        $call_api_obj = new KFPWOO_Call_API();
        $response = $call_api_obj->kfpwoo_call_api($request);

        $login_trusted_device_enabled = KFPWOO_Merchant_Settings::$is_trusted_device_login_allow;
        if (!$login_trusted_device_enabled) {
          session_start();
          unset($_SESSION['KFPWOO_SESSION_ID']);
        }

        /**handle response and event logs*/
        if ($response) {
          //step-up success
          self::$event_logging->kfpwoo_info_logs(self::$constants_text['STEPUP_LOGS'], $session_id, self::$constants_text['STEPUP_SUCCESS']);
          return self::$constants_text['STEPUP_SUCCESS_STATUS'];
        } else {
          //step-up failed
          self::$event_logging->kfpwoo_error_logs(self::$constants_text['STEPUP_LOGS'], $session_id, self::$constants_text['STEPUP_FAILED']);
          return self::$constants_text['STEPUP_SUCCESS_FAILED'];
        }

      } else {
        //return error if $mfa_details is blank
        self::$event_logging->kfpwoo_error_logs(self::$constants_text['STEPUP_LOGS'], '', self::$constants_text['STEPUP_REQUEST_PARAMETER']);
        $response = array("message" => self::$constants_text['STEPUP_REQUEST_PARAMETER'], "status" => false);
        return wp_json_encode($response);
      }
    } else {
      //return error if login functionality is off
      self::$event_logging->kfpwoo_error_logs(self::$constants_text['STEPUP_LOGS'], '', self::$constants_text['LOGIN_FUNC_OFF']);
      $response = array("message" => self::$constants_text['LOGIN_FUNC_OFF'], "status" => false);
      return wp_json_encode($response);
    }
  }
}