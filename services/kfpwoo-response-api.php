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
 * KFPWOO_Response_API
 *
 * RIS Response API endpoints
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/KountResponseAPI
 * @author     Kount Inc. <developer@kount.com>
 *
 */

class KFPWOO_Response_API{
    private static $event_logging,
    $constants_text,
    $order_status,
    $kount_auto_status,
    $kount_ris_response_details,
    $order;

    /**
     * update_order
     * This function is responsible for updating order and meta fields based on RIS response
     * @param  string[] $request
     * @return void
     */
    public static function kfpwoo_update_order($request){
        $constants_obj                    = new KFPWOO_Constants_();
        self::$constants_text             = $constants_obj->kfpwoo_constants_text();
        self::$order_status               = $constants_obj->kfpwoo_order_status();
        self::$kount_auto_status          = $constants_obj->kfpwoo_auto_status();
        self::$kount_ris_response_details = $constants_obj->kfpwoo_ris_response_details();
        self::$event_logging              =  new KFPWOO_Event_logging();

        $order_id = $request['ORDR']; //order id
        //check order_id availability
        if(!isset($order_id)){
            self::$event_logging->kfpwoo_error_logs(self::$constants_text['RIS_RESPONSE_LOGS'],"",self::$constants_text['ORDER_NOT_FOUND']);
            return;
        }
        self::$order = wc_get_order($order_id); 
        if (!self::$order) {
            self::$event_logging->kfpwoo_warning_logs(self::$constants_text['RIS_RESPONSE_LOGS'],"", "wc_get_order returned false for order_id " . $order_id);
            return;
        }
        if (self::$order->get_meta(self::$kount_ris_response_details['KOUNT_TRANSACTION_ID'])) {
            $ris_transaction_id = self::$order->get_meta(self::$kount_ris_response_details['KOUNT_TRANSACTION_ID']);
            self::$event_logging->kfpwoo_warning_logs(self::$constants_text['RIS_RESPONSE_LOGS'],"","RIS Transaction [".$ris_transaction_id."] already exists for order id: ".$order_id .", so these results were discarded: ".json_encode($request));
            return;
        }

        //event logs
        self::$event_logging->kfpwoo_info_logs(self::$constants_text['RIS_RESPONSE_LOGS'],"",self::$constants_text['RIS_RESPONSE_ORDER_ID'].": ". $order_id);

        //reason code
        $reason_code_message = $request['REASON_CODE'] != '' ? ' with Reason Code: '.$request['REASON_CODE'] : '';
        /**order status update for post auth */
        $AUTO = $request['AUTO']; //auto field
        if(KFPWOO_Merchant_Settings::$payment_method == 'post'){
            if($AUTO == self::$kount_auto_status['REVIEW'] && self::$order->has_status(array('pending', 'processing'))){
                // if order status is pending or processing and kount auto status is Review then update order status to On-hold
                self::kfpwoo_update_order_status(self::$order_status['ON_HOLD'], self::kfpwoo_generate_order_note($request, $reason_code_message));
            }
            else if($AUTO == self::$kount_auto_status['ESCALATE'] && self::$order->has_status(array('pending', 'processing'))){
                // if order status is pending or processing and kount auto status is Escalate then update order status to On-hold
                self::kfpwoo_update_order_status(self::$order_status['ON_HOLD'], self::kfpwoo_generate_order_note($request, $reason_code_message));
            }
            else if($AUTO == self::$kount_auto_status['DECLINE'] && self::$order->has_status(array('pending', 'processing', 'on-hold'))){
                // if order status is pending or processing or on-hold and kount auto status is Decline then update order status to Cancelled
                self::kfpwoo_update_order_status(self::$order_status['CANCELLED'], self::kfpwoo_generate_order_note($request, $reason_code_message));
            }
            else if($AUTO == self::$kount_auto_status['ALLOW'] && self::$order->has_status(array('processing'))) {
                // if order status is processing and kount auto status is Allow then update order status to Processing
                self::kfpwoo_update_order_status(self::$order_status['PROCESSING'], self::kfpwoo_generate_order_note($request, $reason_code_message));
            }
        }

        //update meta RIS meta field value
        self::kfpwoo_add_kount_details_for_order($request);
    }

    /**
     * kfpwoo_generate_order_note
     * This function is responsible for generation order notes based on kount auto status value
     * @param  mixed $request
     * @param  mixed $reason_code_message
     * @return string
     */
    public static function kfpwoo_generate_order_note($request, $reason_code_message){
        /**create decision according to auto field value */
        switch ($request['AUTO']){
            case "A": $decision = 'Approve'; break;
            case "D": $decision = 'Decline'; break;
            case "R": $decision = 'Review'; break;
            case "E": $decision = 'Escalate'; break;
        }
        //order notes
        $order_note = self::$constants_text['KOUNT'].' ['.gmdate('Y-m-d H:i:s').']: '.self::$constants_text['RIS_ASSESSMENT_RESPONSE'].$request['SCOR'].self::$constants_text['RECOMMENDATION']." ".$decision.self::$constants_text['TRANSACTION_FOR'].$request['TRAN'].$reason_code_message;
        //order notes logs
        self::$event_logging->kfpwoo_debug_logs(self::$constants_text['RIS_RESPONSE_LOGS'],"",self::$constants_text['ORDER_NOTE_GENERATED'].":". $order_note);
        //return order note
        return $order_note;
    }

    /**
     * kfpwoo_update_order_status
     * It will update order status and add order notes
     * @param  mixed $status
     * @param  mixed $order_note
     * @return void
     */
    public static function kfpwoo_update_order_status($status, $order_note) {
        //update order status
        self::$order->update_status($status, '[Kount]');
        //add order notes
        self::$order->add_order_note($order_note);
        //logs
        self::$event_logging->kfpwoo_info_logs(self::$constants_text['RIS_RESPONSE_LOGS'],"",self::$constants_text['KOUNT_ORDER_STATUS_UPDATE']." : ".$status);
        self::$event_logging->kfpwoo_debug_logs(self::$constants_text['RIS_RESPONSE_LOGS'],"",self::$constants_text['ORDER_NOTE_ADDED']." : ".__($order_note, 'kount-fraud-prevention'));
    }

    /**
     * kfpwoo_add_kount_details_for_order
     * It will add RIS meta details on order
     * @param  mixed $response
     * @return void
     */
    private static function kfpwoo_add_kount_details_for_order($data){
        if (KFPWOO_Merchant_Settings::$isK360) {
            $order_metadata = [
                self::$kount_ris_response_details['KOUNT_RIS_OMNISCORE']       => $data['SCOR'],
                self::$kount_ris_response_details['KOUNT_RIS_RESPONSE']        => $data['AUTO'],
                self::$kount_ris_response_details['KOUNT_RIS_RESPONSE_REASON'] => $data['REASON_CODE'],
                self::$kount_ris_response_details['KOUNT_RIS_RULES_TRIGGERED'] => $data['RULES_TRIGGERED'],
                self::$kount_ris_response_details['KOUNT_TRANSACTION_ID']      => $data['TRAN'],
                self::$kount_ris_response_details['KOUNT_KAPT']                => $data['KAPT'],
                self::$kount_ris_response_details['KOUNT_CARDS']               => $data['CARDS'],
                self::$kount_ris_response_details['KOUNT_EMAIL']               => $data['EMAILS'],
                self::$kount_ris_response_details['KOUNT_DEVICES']             => $data['DEVICES']
            ];
        } else {
            $order_metadata = [
                self::$kount_ris_response_details['KOUNT_RIS_SCORE']           => $data['SCOR'],
                self::$kount_ris_response_details['KOUNT_RIS_OMNISCORE']       => $data['OMNISCORE'],
                self::$kount_ris_response_details['KOUNT_RIS_RESPONSE']        => $data['AUTO'],
                self::$kount_ris_response_details['KOUNT_RIS_RESPONSE_REASON'] => $data['REASON_CODE'],
                self::$kount_ris_response_details['KOUNT_RIS_RULES_TRIGGERED'] => $data['RULES_TRIGGERED'],
                self::$kount_ris_response_details['KOUNT_TRANSACTION_ID']      => $data['TRAN'],
                self::$kount_ris_response_details['KOUNT_GEOX']                => $data['GEOX'],
                self::$kount_ris_response_details['KOUNT_KAPT']                => $data['KAPT'],
                self::$kount_ris_response_details['KOUNT_CARDS']               => $data['CARDS'],
                self::$kount_ris_response_details['KOUNT_EMAIL']               => $data['EMAILS'],
                self::$kount_ris_response_details['KOUNT_DEVICES']             => $data['DEVICES'],
                self::$kount_ris_response_details["KOUNT_SESSION_ID"]          => $data['SESS']
            ];
        }
        
        //updating meta details
        foreach($order_metadata as $key =>$value){
            self::$order->update_meta_data($key, $value);
        }
        self::$order->save();
        //logs
        self::$event_logging->kfpwoo_info_logs(self::$constants_text['RIS_RESPONSE_LOGS'],"",self::$constants_text['RIS_DETAILS_UPDATE'] . " : ".$data['ORDR'] ." : ".json_encode($order_metadata));
    }
}
