<?php

namespace YOOtheme;
use Netdust\Service\Scripts\Script;

return [
    'transforms' => [
        'render' => function ($node) {

            app(Script::class)->add( [
                'is_module'   => true,
                'handle'      => 'youtubevideo',
                'src'         => 'https://cdn.jsdelivr.net/npm/@justinribeiro/lite-youtube@1.5.0/lite-youtube.js',
            ]);


            // Don't render element if content fields are empty
            return Str::length($node->props['video_id']);
        },
    ],

    'updates' => [
        //
    ],
];
