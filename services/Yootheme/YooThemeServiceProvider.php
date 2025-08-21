<?php

namespace Netdust\Services\Yootheme;


use YOOtheme\ImageProvider;
use YOOtheme\Url;
use function YOOtheme\app;

use Netdust\Core\ServiceProvider;
use Netdust\Service\Pages\VirtualPage;



class YooThemeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->container->singleton(\Netdust\Services\Yootheme\YooThemeHelpers::class);

        /*
        add_action('after_setup_theme', function() {
            $app = \YOOtheme\Application::getInstance();
            $app->load(\YOOtheme\Path::get('./bootstrap.php'));
        });*/

    }


}