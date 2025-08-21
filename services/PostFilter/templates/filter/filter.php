<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! isset( $template ) ) {
    return;
}

$taxonomies = $template->get_param('taxonomies',[] );
$results = $template->get_param('results',[] );
$page = $template->get_param('page', '' );
$s = $template->get_param('s', '' );
$layout  = $template->get_param('layout', null );

?>


<div class="ntdst-filter uk-margin-bottom">
<?= $template->render( 'filter/filter_dropdown', [ 'taxonomies'=>$taxonomies, 's'=>$s ] ); ?>

</div>

<div class="uk-text-meta uk-heading-line uk-text-center"><span>Gevonden aantal resultaten: <span id="result-count"><?= !empty($results)?$results->found_posts:'...'; ?></span></span></div>

<div class="ntdst-filter-results uk-margin-large-top">

    <?= $template->render( 'filter/filter_result', [ 'results'=>$results, 'layout'=>$layout ] ); ?>

</div>

<div class="uk-margin-large-top uk-text-center pagination">
    <?= $page ?>
</div>
