<?php

namespace Netdust\eap_csf\Services\Yootheme\src;

use YOOtheme\Config;
use YOOtheme\Path;

class SettingsListener
{
    public static function initCustomizer(Config $config)
    {


        // Add section, as an example using a static JSON configuration
        //$config->addFile('customizer', Path::get('../config/customizer.json'));


        // Add panel, as an example using a dynamic PHP configuration
        $config->set('customizer.panels.netdust-settings', [
            'title' => 'Settings',
            'width' => 400,
            'fields' => [
                'ntdst.duplicate' => [
                    "type"=> "checkbox",
                    "default"=> true,
                    'text' => 'Content Duplication',
                    'description' => 'Enable one-click duplication of pages, posts and custom posts.',
                ],
                'ntdst.order' => [
                    "type"=> "checkbox",
                    "default"=> true,
                    'text' => 'Content Order',
                    'description' => 'Enable custom ordering of various "hierarchical" content types.',
                ],
                'ntdst.media' => [
                    "type"=> "checkbox",
                    "default"=> true,
                    'text' => 'Media Replacement',
                    'description' => 'Easily replace any type of media file with a new one.',
                ],
            ],
        ]);
        $config->set('customizer.sections.settings.fields.settings.items.netdust-settings', 'Netdust');



        // Add context panel
        $config->addFile('customizer', Path::get('./Context/panel.json'));
    }
}
