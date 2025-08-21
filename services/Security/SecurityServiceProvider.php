<?php

namespace Netdust\Services\Security;

use Netdust\App;
use Netdust\Core\ServiceProvider;
use Netdust\Logger\Logger;

class SecurityServiceProvider extends ServiceProvider
{
    public function register() {

        $this->container->tag(
            apply_filters('ntdst.security::modules', [
                \Netdust\Services\Security\modules\Hardening::class,
            ]),
            'ntdst.security::modules'
        );

        App::config()->add( 'security', App::config()->load( dirname(__FILE__ ) . '/config/' ) );

    }

    public function boot() {

        foreach( $this->container->tagged('ntdst.security::modules') as $module ){
            $module->init();
        }

        app()->add_menu_settings( App::config()['security']['admin'] );

    }


}