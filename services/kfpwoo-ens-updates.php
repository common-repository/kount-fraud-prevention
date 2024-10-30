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
 * ENS Updates
 *
 * This class is used to get the ENS Workflow updates and process it.
 *
 * @since       1.0.0
 * @package     Kount
 * @subpackage  Kount/Services
 * @author      Kount Inc. <developer@kount.com>
 *
 */

class KFPWOO_ENS_Updates{
    /**private static variables */
    private static $kount_constants, $ris_meta_constant, $event_logging;

    /**
     * update_order
     * Update order status and adding notes using ENS
     * @param  mixed $request
     * @return void
     */
    public static function kfpwoo_update_order(WP_REST_Request $request){
        $ens_workflow_name  = $request['name']; 
        $transaction_id     = $request['key']['transactionID'];  
        $order_id           = $request['key']['orderNumber']; 
        $old_value          = $request['oldValue']; 
        $new_value          = $request['newValue']['value'];  
        $reason_code        = isset($request['newValue']['reasonCode']) ? $request['newValue']['reasonCode'] : "[not provided]"; 
        $agent_code         = $request['agent']; 
        $occurred_time      = $request['occurred']; 
        //constants class object (responsible for static text)
        $constants_obj = new KFPWOO_Constants_();
        self::$ris_meta_constant = $constants_obj->kfpwoo_ris_response_details();
        self::$kount_constants = $constants_obj->kfpwoo_constants_text();
        //logs class object (responsible for logs)
        self::$event_logging = new KFPWOO_Event_logging();
        // order
        $order = wc_get_order($order_id); 
        if (!$order) {
            self::$event_logging->kfpwoo_warning_logs(self::$kount_constants['ENS_LOGS'], '', "wc_get_order returned false for order_id " . $order_id);
            return;
        }
        //WORKFLOW_STATUS_EDIT
        if($ens_workflow_name === self::$kount_constants['EDIT_ENS_UPDATE']){
            self::$event_logging->kfpwoo_debug_logs(self::$kount_constants['ENS_LOGS'], '', "Stating ".self::$kount_constants['EDIT_ENS_UPDATE']." from ".$old_value." to ".$new_value." for order ".$order_id);
            //if previous kount order status is Approve and Decline nothing will happen
            if($old_value == 'A' || $old_value == 'D'){
                return;
            }
            $order_metadata = [];
            $newStatus = 'on-hold';
            //if new kount order status is approve and order status was processing then order note will add
            if($new_value == 'A' && $order->has_status(array('processing', 'on-hold'))){
                $newStatus = 'processing';
                $order_note = self::$kount_constants['KOUNT'].' ['.$occurred_time.']: '.self::$kount_constants['AGENT'].$agent_code.self::$kount_constants['APPROVED_TRANSACTION_ID'].$transaction_id;
                $order_metadata = [
                    self::$ris_meta_constant['KOUNT_RIS_RESPONSE'] => $new_value,
                    self::$ris_meta_constant['KOUNT_TRANSACTION_ID'] => $transaction_id
                ];
            }
            //if new kount order status is Decline and order status was processing or pending or on-hold then order status will update to cancelled
            else if ($new_value == 'D' && $order->has_status(array('pending', 'processing', 'on-hold'))){
                $newStatus = 'cancelled';
                $order_note = self::$kount_constants['KOUNT'].' ['.$occurred_time.']: '.self::$kount_constants['AGENT'].$agent_code.self::$kount_constants['DECLINE_TRANSACTION_ID'].$transaction_id;
                $order_metadata = [
                    self::$ris_meta_constant['KOUNT_RIS_RESPONSE'] => $new_value,
                    self::$ris_meta_constant['KOUNT_TRANSACTION_ID'] => $transaction_id
                ];
            }
            //update order meta
            self::$event_logging->kfpwoo_debug_logs(self::$kount_constants['ENS_LOGS'], '',self::$kount_constants['ENS_ORDER_RESPONSE']." : ".wp_json_encode($order_metadata));
            if(isset($order_metadata)){
                foreach($order_metadata as $key =>$value){
                    $order->update_meta_data($key, $value);
                }
                $order->save();
            }
            //add order notes
            if(isset($order_note)){
                $order->add_order_note($order_note);
            }
            $order->update_status($newStatus, '[Kount]');
        }
        //WORKFLOW_NOTES_ADD
        else if ($ens_workflow_name === self::$kount_constants['ADD_NOTES_ENS']){
            if($reason_code != ''){
                $order_note = self::$kount_constants['KOUNT'].' ['.$occurred_time.']: '.self::$kount_constants['AGENT'].$agent_code.self::$kount_constants['ADDED_NOTE'].' "'.$new_value.'"'.self::$kount_constants['WITH_REASON'].$reason_code.self::$kount_constants['TO_TRANSACTION'].$transaction_id;
            }
            else{
                $order_note = self::$kount_constants['KOUNT'].' ['.$occurred_time.']: '.self::$kount_constants['AGENT'].$agent_code.self::$kount_constants['ADDED_NOTE'].' "'.$new_value.'"'.self::$kount_constants['TO_TRANSACTION'].$transaction_id;
            }
            if(isset($order_note)){
                $order->add_order_note($order_note);
            }
            self::$event_logging->kfpwoo_debug_logs(self::$kount_constants['ENS_LOGS'], '',self::$kount_constants['ENS_NOTES']." : ".$order_note);
        }
    }
}
