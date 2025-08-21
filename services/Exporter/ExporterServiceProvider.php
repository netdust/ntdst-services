<?php

namespace Netdust\Services\Exporter;

use Netdust\Core\ServiceProvider;
use Netdust\Traits\Mixins;



class ExporterServiceProvider extends ServiceProvider
{


    public function register() {

    }

    public function boot() {

    }

    public function export( string $id, array $param ): ?Exporter {
        return null;
    }


}