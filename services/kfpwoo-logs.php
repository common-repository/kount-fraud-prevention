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
 * Event logging
 *
 * This class is responsible for event logging
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/KFPWOO_Event_logging
 * @author     Kount Inc. <developer@kount.com>
 *
 */

require plugin_dir_path( dirname( __FILE__ ) ) . '/vendor/autoload.php';
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Logger;

class KFPWOO_Event_logging {

    /**public variables */
    public $level, $logger, $upload_dir;

    /**
     * __construct
     * It will all automatically when the call load
     */
    public function __construct(){
        $this->level        =   KFPWOO_Merchant_Settings::$logs_level;
        $uploads            =   wp_upload_dir();
        $this->upload_dir   =   $uploads['basedir'] ; //base directory url
        $this->upload_dir   =   $this->upload_dir . '/wc-logs'; //logs folder url
     }

     public function kfpwoo_get_log_filename() {
        return $this->upload_dir.'/kount_logs';
     }
    /**
     * kfpwoo_debug_logs
     * It will logs all debug, error, info level logs
     * @param  mixed $channel_name
     * @param  mixed $session_ID
     * @param  mixed $msg
     * @return void
     */
    public function kfpwoo_debug_logs($channel_name, $session_ID, $msg) {
        if($this->level != "debug" ){
            return;
        }
        //channel for logs
        $this->logger = new Logger($channel_name);
        //Rotation logs
        $this->logger->pushHandler(new RotatingFileHandler($this->upload_dir.'/kount_logs.log', Logger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler());
        //event logs
        $this->logger->debug(($session_ID != ""? ('Session ID: '.$session_ID.', Message : '.$msg) : ('Message : '.$msg)));
    }

   /**
    * kfpwoo_info_logs
    * It will logs all error and info level logs
    * @param  mixed $channel_name
    * @param  mixed $session_ID
    * @param  mixed $msg
    * @return void
    */
   public function kfpwoo_info_logs($channel_name, $session_ID, $msg){
        if($this->level != "info" && $this->level != "debug"){
            return;
        }
        $this->logger = new Logger($channel_name);
        //Rotation logs
        $this->logger->pushHandler(new RotatingFileHandler($this->upload_dir.'/kount_logs.log', Logger::INFO));
        $this->logger->pushHandler(new FirePHPHandler());
        //event logs
        $this->logger->info(($session_ID != ""? ('Session ID : '.$session_ID.', Message : '.$msg) : ('Message : '.$msg)));
   }

   /**
    * kfpwoo_error_logs
    * It will logs only error level logs
    * @param  mixed $channel_name
    * @param  mixed $session_ID
    * @param  mixed $msg
    * @return void
    */
   public function kfpwoo_error_logs($channel_name, $session_ID, $msg){
        if($this->level != "error" && $this->level != "debug" && $this->level != "info" ){
            return;
        }
        $this->logger = new Logger($channel_name);
        //Rotation logs
        $this->logger->pushHandler(new RotatingFileHandler($this->upload_dir.'/kount_logs.log', Logger::ERROR));
        $this->logger->pushHandler(new FirePHPHandler());
        //event logs
        $this->logger->error(($session_ID != ""? ('Session ID: '.$session_ID.', Message : '.$msg) : ('Message : '.$msg)));
   }

   /**
       * kfpwoo_warning_logs
       * It will logs only warning level logs
       * @param  mixed $channel_name
       * @param  mixed $session_ID
       * @param  mixed $msg
       * @return void
       */
   public function kfpwoo_warning_logs($channel_name, $session_ID, $msg){
       if($this->level != "info" && $this->level != "debug"){
            return;
       }
       $this->logger = new Logger($channel_name);
       //Rotation logs
       $this->logger->pushHandler(new RotatingFileHandler($this->upload_dir.'/kount_logs.log', Logger::WARNING));
       $this->logger->pushHandler(new FirePHPHandler());
       //event logs
       $this->logger->warning(($session_ID != ""? ('Session ID: '.$session_ID.', Message : '.$msg) : ('Message : '.$msg)));
   }

}
