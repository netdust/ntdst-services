<?php

namespace Netdust\eap_csf\Services\Yootheme\src;
class MyTypeProvider
{
    public static function get($id)
    {
        // Query objects
        return (object)['my_field' => "The data for id: {$id}"];
    }
}
