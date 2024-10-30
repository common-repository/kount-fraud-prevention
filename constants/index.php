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
 * Constants_
 *
 * It contains all text
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/Constants
 * @author     Kount Inc. <developer@kount.com>
 *
 */
Class KFPWOO_Constants_ {

    /**
     * kfpwoo_constants_text
     * Content text of plugin
     * @return string[]
     */
    public function kfpwoo_constants_text(){
        $constants=[
            'PLUGIN_VERSION_VALUE'=>'2.0.1',
            'NO_EMAIL_KOUNT'=>'noemail@kount.com',
            'PAYMENT_SUCCESS_STATUS' => 'success',
            'PAYMENT_FAILED_STATUS' => 'failed',
            'DEFAULT_PAYMENT_TYPE' => 'NONE',
            
            //RIS meta fields
            'KOUNT_K360_RESPONSE' => 'Response',
            'KOUNT_K360_RULES_TRIGGERED' => 'Rules Triggered',
            'KOUNT_K360_OMNISCORE'=> 'Omniscore',
            'KOUNT_RESPONSE'=>'Kount Response',
            'KOUNT_RIS_SCORE'=>__('RIS Score','kount-fraud-prevention'),
            'KOUNT_RIS_OMNISCORE'=>__('RIS Omniscore','kount-fraud-prevention'),
            'KOUNT_RIS_RESPONSE'=>__('RIS Response','kount-fraud-prevention'),
            'KOUNT_RIS_RESPONSE_REASON'=>__('RIS Response Reason','kount-fraud-prevention'),
            'KOUNT_RIS_RULES_TRIGGERED'=>__('RIS Rules Triggered','kount-fraud-prevention'),
            'KOUNT_TRANSACTION_ID'=>__('Transaction ID (TRAN)','kount-fraud-prevention'),
            'KOUNT_TRANSACTION_ID_PUT'=>'kountTransactionID',
            'KOUNT_GEOX'=>__('Persona Country (GEOX)','kount-fraud-prevention'),
            'KOUNT_KAPT'=>__('Data Collector Present (KAPT)','kount-fraud-prevention'),
            'KOUNT_CARDS'=>__('Cards associated with Persona (CARDS)','kount-fraud-prevention'),
            'KOUNT_EMAIL'=>__('Emails associated with Persona (EMAILS)','kount-fraud-prevention'),
            'KOUNT_DEVICES'=>__('Device associated with Persona (DEVICES)','kount-fraud-prevention'),
            'KOUNT_SESSION_ID'=>__('Session ID (SESS)','kount-fraud-prevention'),
            'KOUNT_PAYMENT_SITE_ID'=>'kountPaymentSiteID',
            'KOUNT_MERCHANT_ID'=>'kountMerchantID',
            'WOO_CONSUMER_KEY'=>'wooConsumerKey',
            'WOO_CONSUMER_SECRET'=>'wooConsumerSecret',
            'WOO_API_BASE_URL'=>'wooAPIBaseURL',
            'RESPONSE_STATUS'=>'success',
            'RESPONSE_MESSAGE'=>'message',
            'ORDER_ID_ERROR'=>__('Order ID required.','kount-fraud-prevention'),
            'DATA_NOT_FOUND'=>__('Data not found','kount-fraud-prevention'),
            'AUTHORIZATION_FAILED'=>__('Authorization failed.','kount-fraud-prevention'),
            'ADMIN_CREATED' => 'adminCreated',
            'CUSTOMER_IP' => 'customerIp',
            'NEEDS_PROCESSING' => 'needsProcessing',

            /**post auth*/
            'WOO_SITE'=>'wooSite',
            'WOO_STORE_ID'=>'wooStoreID',
            'PLUGIN_VERSION'=>'pluginVersion',
            'WOO_AUTHENTICATION_TOKEN' =>'wooAuthenticationToken',
            'MODE'=>'mode',
            'WOO_PLATFORM_VERSION'=>'wooPlatformVersion',
            'SOURCE'=>'source',
            'CART_ID'=>'cartID',
            'BROWSER_IP'=>'browserIp',
            'ORDER_NUMBER'=>'orderNumber',
            'CUSTOMER_ID'=>'customerID',
            'USER_NAME'=>'userName',
            'EMAIL_ADDRESS'=>'emailAddress',
            'FIRST_NAME'=>'firstName',
            'LAST_NAME'=>'lastName',
            'LINE1'=>'line1',
            'LINE2'=>'line2',
            'CITY'=>'city',
            'STATE'=>'state',
            'POSTAL_CODE'=>'postalCode',
            'COUNTRY_CODE'=>'countryCode',
            'PHONE_NUMBER'=>'phoneNumber',
            'SHIPPING_METHOD_ID'=>'shippingMethodID',
            'SHIPPING_METHOD'=>'shippingMethod',
            'TYPE'=>'type',
            'PAYMENT_TRANSACTION_ID'=>'id',
            'PAYMENT_METHOD'=>'paymentMethod',
            'PAYMENT_METHOD_TITLE'=>'paymentMethodTitle',
            'PAYMENT_TYPE'=>'paymentType',
            'PAYMENT_ENCRYPTION'=>'paymentEncryption',
            'TOKEN'=>'token',
            'BIN'=>'bin',
            'LAST4'=>'last4',
            'CURRENCY'=>'currency',
            'IS_TEST'=>'isTest',
            'TOTAL'=>'total',
            'DISCOUNT_TOTAL'=>'discountTotal',
            'COUPON_TOTAL'=>'couponTotal',
            'COUPON_CODES'=>'couponCodes',
            'NAME'=>'name',
            'AMOUNT'=>'amount',
            'STATUS'=>'status',
            'AVS_CODE'=>'avsCode',
            'CVV_CODE'=>'cvvCode',
            'ID'=>'id',
            'SKU'=>'sku',
            'PRODUCT_ID'=>'productID',
            'PRODUCT_TYPE'=>'productType',
            'DESCRIPTION'=>'description',
            'PRICE'=>'price',
            'QUANTITY'=>'quantity',
            'IS_DIGITAL'=>'isDigital',
            'IS_GIFT_CARD'=>'isGiftCard',
            'VALUE'=>'value',
            'AUTHORIZATION'=>'authorization',
            'CARD'=>'CARD',
            'MASK'=>'MASK',
            'KHASH'=>'KHASH',
            'MODE_NAME'=>'post',
            'CUSTOMER_DETAILS'=>'customer',
            'BILLING_DETAILS'=>'billing',
            'SHIPPING_DETAILS'=>'shipping',
            'TRANSACTION_DETAILS'=>'transaction',
            'SHOPPING_CART'=>'shoppingCart',
            'ORDER_PROPERTIES'=>'orderProperties',
            'K360_API_KEY'=>'k360ApiKey',

            //Request parameter
            'API_URL'=>'api_url',
            'API_KEY'=>'api_key',
            'PAYLOAD'=>'payload',
            'METHOD'=>'method',
            'HEADER'=>'header',
            'CALL_FORM'=>'call_from',

            //messages
            'DEVICE_DATA_COLLECTOR'=>__('Device data collector','kount-fraud-prevention'),
            'DDC_URL'=>__('DDC URL','kount-fraud-prevention'),
            'MISSING_REQUIRED_REQUEST_FILEDS'=>__('Missing required request fields.','kount-fraud-prevention'),
            'API_RESPONSE'=>__('API response','kount-fraud-prevention'),
            'UNKNOWN_ERROR_OCCURED'=>'Unknown error occurred',
            'RIS_RESPONSE_ORDER_ID'=>__('RIS response order id','kount-fraud-prevention'),
            'ORDER_NOT_FOUND'=>__('Order not found.','kount-fraud-prevention'),
            'INVALID_ORDER_ID_ERROR'=>__('Invalid order ID.','kount-fraud-prevention'),
            'KOUNT_ORDER_STATUS_UPDATE'=>__('Order status updated','kount-fraud-prevention'),
            'ORDER_NOTE_ADDED'=>__('Order note added','kount-fraud-prevention'),
            'ORDER_NOTE_GENERATED'=> __('Generated order note','kount-fraud-prevention'),
            'TRANSACTION_ID_NOT_FOUND'=>'Kount transaction ID not found.',
            'INVALID_ENS_WORKFLOW_MODE'=>'Invalid ENS workflow mode.',
            'ENS_WORKFLOW_MODE_REQUIRED'=>'ENS workflow mode required.',
            'RIS_DETAILS_UPDATE'=>__('RIS details updated on order','kount-fraud-prevention'),
            'INVALID_KOUNT_TRANSACTION_ID'=>'Invalid kount transaction ID.',
            'PAYMENT_STATUS'=>__('Payment status','kount-fraud-prevention'),
            'POST_AUTH_SUCCESS'=>__('Post auth call successfully','kount-fraud-prevention'),
            'PRE_AUTH_SUCCESS'=>__('Pre auth call successfully','kount-fraud-prevention'),
            'POST_AUTH_FAILED'=>__('Post auth call failed','kount-fraud-prevention'),
            'PRE_AUTH_FAILED'=>__('Pre auth call failed','kount-fraud-prevention'),
            'KOUNT_AUTO_STATUS'=>'Kount RIS auto status',
            'SETTINGS_SAVED_SUCCESSFULLY'=>__('Admin setting saved successfully.','kount-fraud-prevention'),
            'SETTING_UPDATED_SUCCESSFULLY'=>__('Admin setting updated successfully.','kount-fraud-prevention'),
            'LOG_API_URL'=>__('API URL','kount-fraud-prevention'),
            'LOG_METHOD'=>__('Method','kount-fraud-prevention'),
            'ENS_ORDER_RESPONSE'=>__('Order response from ENS','kount-fraud-prevention'),
            'ENS_NOTES'=>__('Notes update from ENS','kount-fraud-prevention'),

            //logs
            'DDC_LOGS'=>'DDC_LOGS',
            'API_LOGS'=>'API_LOGS',
            'POST_AUTH_LOGS'=>'POST_AUTH_LOGS',
            'PRE_AUTH_LOGS'=>'PRE_AUTH_LOGS',
            'RIS_RESPONSE_LOGS'=>'RIS_RESPONSE_LOGS',
            'INSTALL_LOGS'=>'INSTALL_LOGS',
            'UNINSTALL_LOGS'=>'UNINSTALL_LOGS',
            'ENS_LOGS'=>'ENS_LOGS',

            /** ENS Callback */
            'KFPWOO_SESSION_ID'=>'kountSessionID',
            'LINE_ITEM_PROPERTIES'=>'lineItemProperties',
            'PRE_AUTH_MODE'=>'pre',
            'EDIT_ENS_UPDATE'=>'WORKFLOW_STATUS_EDIT',
            'ADD_NOTES_ENS'=>'WORKFLOW_NOTES_ADD',
            'KOUNT'=>'[Kount]',
            'AGENT'=>'Agent ',
            'APPROVED_TRANSACTION_ID'=>' has updated the order to Approve for Transaction ID: ',
            'DECLINE_TRANSACTION_ID'=>' has updated the order to Decline for Transaction ID: ',
            'ADDED_NOTE'=>' has added the following note',
            'WITH_REASON'=>' with reason code: ',
            'TO_TRANSACTION'=>' to Transaction ID:',
            'RIS_ASSESSMENT_RESPONSE'    => 'The Kount Risk Assessment responded with an Omniscore of ',
            'RECOMMENDATION'    => ' and a recommendation of',
            'TRANSACTION_FOR'   =>  ' for transaction ID: ',

            //default response
            'DECISION' => 'decision',
            'DEFAULT_RIS_DECISION' =>'A',
            'TRANSACTION_ID'=>'transactionID',
        ];
        return $constants;
    }

    /**
     * kfpwoo_payment_type
     * Returning all payment type
     * @return array
     */
    public function kfpwoo_payment_type(){
        $payment_type=[
            'applepay' => 'APAY',
            'creditcard' => 'CARD',
            'check' => 'CHEK',
            'greendotmoneypack' => 'GDMP',
            'googlecheckout' => 'GOOG',
            'billmelater' => 'BLML',
            'giftcard' => 'GIFT',
            'bpay' => 'BPAY',
            'neteller' => 'NETELLER',
            'giropay' => 'GIROPAY',
            'elv' => 'ELV',
            'mercadepago' => 'MERCADE_PAGO',
            'singleeuropaymentsarea' => 'SEPA',
            'interac' => 'INTERAC',
            'cartebleue' => 'CARTE_BLEUE',
            'poli' => 'POLI',
            'skrill' => 'SKRILL',
            'moneybookers' => 'SKRILL',
            'sofort' => 'SOFORT'
        ];
        return $payment_type;
    }

    /**
     * kfpwoo_order_status
     * Returning all order status
     * @return array
     */
    public function kfpwoo_order_status(){
        $order_status=[
            'ON_HOLD' => 'on-hold',
            'CANCELLED' => 'cancelled',
            'PROCESSING' => 'processing'
        ];
        return $order_status;
    }

    /**
     * kfpwoo_auto_status
     * Returing kount status
     * @return array
     */
    public function kfpwoo_auto_status(){
        $kount_auto_status=[
            'REVIEW' => 'R',
            'ESCALATE' => 'E',
            'ALLOW' => 'A',
            'DECLINE' => 'D'
        ];
        return $kount_auto_status;
    }

    public function kfpwoo_ris_response_details(){
        $kount_response_details=[
            'KOUNT_RIS_SCORE' => 'kount_RIS_score',
            'KOUNT_RIS_OMNISCORE' => 'kount_RIS_omniscore',
            'KOUNT_RIS_RESPONSE' => 'kount_RIS_response',
            'KOUNT_RIS_RESPONSE_REASON' => 'kount_RIS_response_reason',
            'KOUNT_RIS_RULES_TRIGGERED' => 'kount_RIS_rules_triggered',
            'KOUNT_TRANSACTION_ID' => 'kount_transaction_id',
            'KOUNT_GEOX' => 'kount_GEOX',
            'KOUNT_KAPT' => 'kount_KAPT',
            'KOUNT_CARDS' => 'kount_CARDS',
            'KOUNT_EMAIL' => 'kount_EMAIL',
            'KOUNT_DEVICES' => 'kount_DEVICES',
            'KOUNT_SESSION_ID' => 'kount_SESSION_ID'
        ];
        return $kount_response_details;
    }

    /**
     * kfpwoo_pre_auth_constants
     *
     * @return array
     */
    public function kfpwoo_pre_auth_constants(){
        $pre_auth_constants = [
            'MODE'  =>  'pre',
        ];
        return $pre_auth_constants;
    }
}
