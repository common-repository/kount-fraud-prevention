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
 * Delete Kount Logs
 *
 * This is used for deleting logs
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/KFPWOO_Delete_Kount_Logs
 * @author     Kount Inc. <developer@kount.com>
 *
 */

class KFPWOO_Delete_Kount_Logs {
    /**
     * Load
     * deleting older logs
     * @return void
     */
    public function kfpwoo_load_logs(){
        /**days for logs delete */
        $delete_days        =   KFPWOO_Merchant_Settings::$delete_logs_in;
        $days               =   "-".$delete_days." days";
        $till_date          =   gmdate('Y-m-d', strtotime($days));
        /**upload directory url */
        $uploads            =   wp_upload_dir();
        $kount_logs_dir     =   $uploads['basedir']."/wc-logs";
        /**files arr */
        $files_arr          =   [];

        if(is_dir($kount_logs_dir)){
            $files  =   opendir($kount_logs_dir);
            if($files){
                while(($filename = readdir($files)) != false){
                    if($filename != '.' && $filename !=".." && strpos($filename, 'kount_logs-') !== false){
                        /**getting creation dates of files */
                        $file_date   =  str_replace("kount_logs-","",$filename);
                        $file_date   =  str_replace(".log","",$file_date);
                        /**array of dates */
                        array_push($files_arr, $file_date);
                    }
                }
                foreach($files_arr as $file_create_dd){
                    if ($till_date  >= $file_create_dd ){
                        /**delete file */
                        unlink($kount_logs_dir."/kount_logs-".$file_create_dd.".log");
                    }
                }
            }
        }

    }

}
//object of class
$obj_delete  =  new KFPWOO_Delete_Kount_Logs();
//calling function
$obj_delete->kfpwoo_load_logs();
