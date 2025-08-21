<?php



return function ($app) {


    /**
     * register the  serviceproviders
     */
    $providers = [
        // Services
        \Netdust\Services\PostFilter\PostFilterServiceProvider::class,
        //\Netdust\Services\Security\SecurityServiceProvider::class,
        \Netdust\Services\Settings\SettingsServiceProvider::class,
        \Netdust\Services\Yootheme\YooThemeServiceProvider::class,
        \Netdust\Services\Exporter\ExporterServiceProvider::class,
        //\Netdust\Services\Vite\ViteServiceProvider::class,
    ];


    foreach($providers as $key => $value ) {
        if( is_array($value) ) // map alias too
            call_user_func_array( [$app->container(),'register'], $value );
        else {
            $app->container()->register( $value );
        }
    };

};