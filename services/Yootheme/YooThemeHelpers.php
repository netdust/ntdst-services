<?php

namespace Netdust\Services\Yootheme;


use YOOtheme\ImageProvider;
use YOOtheme\Url;
use function YOOtheme\app;

use Netdust\Service\Pages\VirtualPage;



class YooThemeHelpers
{

    public function make( string $uri, string $title, string $template = null ):VirtualPage {
        return new Yootheme_VirtualPage( $uri, $title, $template );
    }

    public function image( string $thumbnail_url, array $attr = [], int|string $width = '400', int|string $height = 'auto', bool $caption = false ):void {
        [$config, $view] = app(\YOOtheme\Config::class, \YOOtheme\View::class);

        if (!$src = $thumbnail_url ) {
            return;
        }

        $image = app(ImageProvider::class);
        $meta = get_post_meta(get_post_thumbnail_id());
        $src = Url::relative(set_url_scheme($src, 'relative'));
        $alt = $meta['_wp_attachment_image_alt'] ?? '';

        if ($view->isImage($src) == 'svg') {
            $thumbnail = $image->replace($view->image($src, ['width' => $width, 'height' => $height, 'loading' => 'lazy', 'alt' => $alt]));
        } else {
            $thumbnail = $image->replace($view->image([$src, 'thumbnail' => [$width, $height], 'srcset' => true], ['loading' => 'lazy', 'alt' => $alt]));
        }


        if ($thumbnail) {
            ?>
            <div<?= $view->attrs($attr) ?> property="image" typeof="ImageObject">
                <meta property="url" content="<?= $thumbnail_url ?>">
                <?= $thumbnail ?>
                <?php if( $caption ): ?><figcaption class="uk-text-meta"><?= get_the_post_thumbnail_caption(); ?></figcaption><?php endif; ?>
            </div>
            <?php
        }

    }


}