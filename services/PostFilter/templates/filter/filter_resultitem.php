<?php

use Netdust\App;
use Netdust\Services\Yootheme\YooThemeHelpers;
use Netdust\Services\Yootheme\YooThemeServiceProvider;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! isset( $template ) ) {
    return;
}

$attrs_image['class'][] = 'uk-text-center';

?>

<div>
    <a class="uk-panel uk-flex uk-flex-column uk-flex-top uk-margin-remove-first-child uk-link-toggle" href="<?= $template->get_param('permalink', '#' ); ?>" aria-label="<?= $template->get_param('title', 'no post' ); ?>">

        <?= App::container()->get( YooThemeHelpers::class )->image( $template->get_param('image', null ), $attrs_image, 122, 180 ); ?>

        <h3 class="el-title uk-h5 uk-text-primary uk-margin-small-top uk-margin-remove-bottom">
            <span class="my_term-archive uk-text-small uk-text-emphasis uk-text-normal" style="font-size: .8rem;"><?= $template->get_param('meta', '' ); ?></span><br><?= $template->get_param('title', null ); ?>
        </h3>

        <div class="el-content uk-panel uk-text-small uk-margin-small-top"><?= $template->get_param('excerpt', null ); ?></div>

    </a>
</div>