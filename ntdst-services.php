<?php
/**
 *
 * @link              https://netdust.be
 * @since             1.0.0-dev
 * @package           Netdust\ntdst
 * @author            Stefan Vandermeulen
 *
 * @wordpress-plugin
 * Plugin Name:       NTDST Services library
 * Plugin URI:        https://netdust.be
 * Description:       A Plugin with services to use across online apps.
 * Version:           1.0.0
 * Author:            Stefan Vandermeulen
 * Author URI:        https://netdust.be
 * Text Domain:       ntdst_services
 */


defined( 'ABSPATH' ) || exit;

add_action( 'application/register', '_load_netdustservices', 99 );
function _load_netdustservices( \Netdust\ApplicationProvider $app ) {
    $path = dirname(__FILE__). '/register/';

    foreach (glob($path . '*.php') as $file) {
        call_user_func(function ($bootstrap) use ( $app ) {
            $bootstrap($app);
        }, require_once($file));
    }
}
