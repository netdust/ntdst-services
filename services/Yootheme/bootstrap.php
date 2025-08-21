<?php

use Netdust\eap_csf\Services\Yootheme\src\Context\ContextTransform;
use Netdust\eap_csf\Services\Yootheme\src\Context\ContextListener;
use Netdust\eap_csf\Services\Yootheme\src\SettingsListener;
use Netdust\eap_csf\Services\Yootheme\src\StyleListener;


use YOOtheme\Builder;
use YOOtheme\Path;
use YOOtheme\Theme\Styler\StylerConfig;


return [

    'theme' => [
        'styles' => [
            'components' => [
                'ntdst' => Path::get('./assets/less/ntdst.less'),
            ],
        ],
    ],

    'config' => [
        'netdust' => [
            'version' => '2.2.14',
            'build' => '0531.1026',
        ],
        StylerConfig::class => __DIR__ . '/config/styler.json',
    ],

    'events' => [


        'builder.type' => [
            ContextListener::class => 'onBuilderType',
        ],

        /*
        // Add asset files
        'theme.head' => [
            AssetsListener::class => 'initHead',
        ],*/


        // Add settings Panels
        'customizer.init' => [
            SettingsListener::class => 'initCustomizer',
        ],

        /*
        // Add custom demo source
        'source.init' => [
            SourceListener::class => 'initSource',
        ],*/


        // Add styler config listener
        StylerConfig::class => [
            StyleListener::class => 'config'
        ],

    ],

    // Add builder elements
    'extend' => [

        Builder::class => function (Builder $builder, $app) {
            $builder->addTypePath(Path::get('./elements/*/element.json'));

            if (!(apply_filters('element_context_yt_disable', false))) {
                $builder->addTransform('render', $app(ContextTransform::class));
            }
        },

    ],

];
