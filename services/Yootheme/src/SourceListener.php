<?php

namespace Netdust\eap_csf\Services\Yootheme\src;

use Netdust\eap_csf\Services\Yootheme\src\Type\MyQueryType;
use Netdust\eap_csf\Services\Yootheme\src\Type\MyType;
use YOOtheme\Builder\Source;

class SourceListener
{
    /**
     * @param Source $source
     */
    public static function initSource($source)
    {
        $source->objectType('MyType', MyType::config());
        $source->queryType(MyQueryType::config());
    }
}
