<?php

namespace Netdust\Services\Vite;

use Netdust\App;
use Netdust\Core\ServiceProvider;
use Netdust\Logger\Logger;

class ViteServiceProvider extends ServiceProvider
{

    public function register() {

        $conf = App::config()->load( dirname(__FILE__ ) . '/config/' );

        $this->container->singleton(
            Vite::class,
            new Vite( false, $conf['env'] )
        );
    }

    public function boot() {
        $this->container->get(Vite::class)->init();
    }

}