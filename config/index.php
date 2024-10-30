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
 * Config_
 *
 * API endpoints configuration
 *
 * @since      1.0.0
 * @package    Kount
 * @subpackage Kount/Config
 * @author     Kount Inc. <developer@kount.com>
 *
 */
class KFPWOO_Config_
{
    /**
     * config_
     *
     * @return array
     */
    public function config_()
    {
        $config_data = [
            'BASE_URL'            => site_url(),
            'RIS_ENDPOINT'        => '/woo/orderAssessment',
            'INSTALL_ENDPOINT'    => '/woo/install',
            'WOO_API_ENDPOINTS'   => 'kount',
            'RIS_URL'             => $this::which_url(KFPWOO_REQUEST_ROUTER_URL, KFPWOO_SECONDARY_REQUEST_ROUTER_URL),
            'DDC_URL'             => $this::which_url(KFPWOO_DDC_URL, KFPWOO_SECONDARY_DDC_URL),
            'AWC_URL'             => $this::which_url(KFPWOO_AWC_URL, KFPWOO_SECONDARY_AWC_URL),
            'K360_URL'            => $this::which_url(KFPWOO_K360_URL, KFPWOO_SECONDARY_K360_URL),
            'CUSTOMER_ASSESSMENT' => '/woo/customerAssessment'
        ];
        return $config_data;
    }

    private static function which_url($primary, $secondary)
    {
        if (KFPWOO_Merchant_Settings::$test_mode_enable) {
            return $secondary;
        }
        return $primary;
    }
}
