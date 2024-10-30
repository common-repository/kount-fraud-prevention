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


// a helper function to lookup "env_FILE", "env", then fallback
if (!function_exists('k_getenv_docker')) {
	function k_getenv_docker($env, $default)
	{
		if ($fileEnv = getenv($env . '_FILE')) {
			return rtrim(file_get_contents($fileEnv), "\r\n");
		} else if (($val = getenv($env)) !== false) {
			return $val;
		} else {
			return $default;
		}
	}
}

// production environment variables - IMPORTANT: no whitespace at end of tag
define('KFPWOO_REQUEST_ROUTER_URL',           k_getenv_docker('KOUNT_REQUEST_ROUTER',           'https://api.kount.com'));
define('KFPWOO_DDC_URL',                      k_getenv_docker('KOUNT_DDC_URL',                  'https://ssl.kaptcha.com/collect/sdk'));
define('KFPWOO_AWC_URL',                      k_getenv_docker('KOUNT_AWC_URL',                  'https://portal.kount.net/workflow/detail.html?id='));
define('KFPWOO_K360_URL',                     k_getenv_docker('KOUNT_K360_URL',                 'https://app.kount.com/event-analysis-v2/order/'));
define('KFPWOO_ENS_URL',                      k_getenv_docker('KOUNT_ENS_URL',                  'https://ens-orchestrator.prod06.prd.eds.us-west-2.aws.efx/ens/platform/woocommerce/shop/'));
define('KFPWOO_SECONDARY_REQUEST_ROUTER_URL', k_getenv_docker('KOUNT_SECONDARY_REQUEST_ROUTER', 'https://api-sandbox.kount.com'));
define('KFPWOO_SECONDARY_DDC_URL',            k_getenv_docker('KOUNT_SECONDARY_DDC_URL',        'https://tst.kaptcha.com/collect/sdk'));
define('KFPWOO_SECONDARY_AWC_URL',            k_getenv_docker('KOUNT_SECONDARY_AWC_URL',        'https://portal.test.kount.net/workflow/detail.html?id='));
define('KFPWOO_SECONDARY_K360_URL',           k_getenv_docker('KOUNT_SECONDARY_K360_URL',       'https://app.kount.com/event-analysis-v2/order/'));
define('KFPWOO_SECONDARY_ENS_URL',            k_getenv_docker('KOUNT_ENS_URL',                  'https://ens-orchestrator.sandbox13.uat.eds.us-west-2.aws.efx/ens/platform/woocommerce/shop/'));
