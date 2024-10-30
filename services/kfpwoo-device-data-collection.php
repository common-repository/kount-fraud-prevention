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
 * KFPWOO_Device_Data_Collection
 *
 * This is used to class to collect device data and sent to the Kount Server
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/DataCollector
 * @author     Kount Inc. <developer@kount.com>
 *
 */

class KFPWOO_Device_Data_Collection
{
    /**public variables */
    public $event_logging, $kount_config, $constants_text;

    /**
     * __construct
     * It will all automatically when the call load
     * @return void
     */
    public function __construct(){
        //configuration settings
        $config                         =     new KFPWOO_Config_;
        $this->kount_config             =     $config->config_();
        //constants class object (responsible for static text)
        $costants_obj                   =     new KFPWOO_Constants_();
        $this->constants_text           =     $costants_obj->kfpwoo_constants_text();
        //logs class object (responsible for logs)
        $this->event_logging            =     new KFPWOO_Event_logging;
    }

    /**
     * kfpwoo_device_data_collector_sdk
     * The Kount Device Data Collection SDK is hosted by Kount, and needs to be downloaded dynamically to be used on a web page.
     * This url can be used to download the SDK.
     * @return void
     */
    public function kfpwoo_device_data_collector_sdk()
    {
        if (!isset($_COOKIE['KFPWOO_SESSION_ID'])) {
            $s = KFPWOO_Merchant_Settings::kfpwoo_session_id();
            setcookie('KFPWOO_SESSION_ID', $s);
            $_COOKIE['KFPWOO_SESSION_ID'] = $s;
        }

        $current_session_id = sanitize_text_field($_COOKIE['KFPWOO_SESSION_ID']);
        //merchant id
        $merchant_id=KFPWOO_Merchant_Settings::$merchant_id;
        //ddc url
        $data_collector_url = $this->kount_config['DDC_URL'];
        
        //register and enqueue script
        wp_register_script('kount-script', $data_collector_url.'?m='.$merchant_id.'&s='.$current_session_id, array ('jquery'),null, false);
        wp_enqueue_script('kount-script');
        $this->event_logging->kfpwoo_debug_logs($this->constants_text['DDC_LOGS'], $current_session_id, $this->constants_text['DEVICE_DATA_COLLECTOR']." url=".$data_collector_url.'?m='.$merchant_id.'&s='.$current_session_id);
        $this->event_logging->kfpwoo_debug_logs($this->constants_text['DDC_LOGS'], $current_session_id, $this->constants_text['DDC_URL']." : ".$data_collector_url);
    }
}
