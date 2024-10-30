<?php

/**
 * Plugin Name:       Kount Fraud Prevention
 * Plugin URI:        https://kount.com/partners/woocommerce/
 * Description:       Turn on Kount’s industry-leading fraud protection to immediately reduce chargebacks and manual reviews, while increasing approval rates and revenue—without adding friction.
 * Version:           2.0.1
 * Author:            Kount Inc.
 * Author URI:        https://kount.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       kount
 * Domain Path:       /languages
 *
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


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'KFPWOO_VERSION', '2.0.1' );

/**
 * Kount Settings name
 */
define('KFPWOO_SETTINGS','kms');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-kount-activator.php
 */
function kfpwoo_activate_kount() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kount-activator.php';
	KFPWOO_Activator::kfpwoo_activate();
}

register_activation_hook( __FILE__, 'kfpwoo_activate_kount' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-kount.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_kfpwoo() {

	$plugin = new KFPWOO();
	$plugin->kfpwoo_run();

}
run_kfpwoo();
add_action( 'wp_print_scripts', 'kfpwoo_list_scripts' );

function kfpwoo_list_scripts() {
    $event_logging = new KFPWOO_Event_logging();
    global $wp;
    $event_logging->kfpwoo_debug_logs("SCRIPTS_LOGS", "", "Scripts for url : ".home_url( $wp->request ) );
    global $wp_scripts;
    foreach( $wp_scripts->queue as $handle ) {
    // Print all script names
    // $event_logging->kfpwoo_debug_logs("SCRIPTS_LOGS", "", "Script Name: ".$handle);
        if($handle === "kount" || $handle === "kount-script") {
            $event_logging->kfpwoo_debug_logs("SCRIPTS_LOGS", "", "Kount Script src: ".$handle." : ".$wp_scripts->registered[$handle]->src);
        }
    }
}