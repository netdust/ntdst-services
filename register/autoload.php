<?php


return function ($app) {

    \Netdust\Core\AutoLoader::setup_autoloader( [
        'Netdust\Services\\'=> dirname(__DIR__, 1) . '/services'
    ] );

};