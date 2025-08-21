<?php

// Element
$el = $this->el('div');

?>

<?= $el($props, $attrs) ?>

    <?php if ($props['video_id']) : ?>
        <lite-youtube videoid="<?= $props['video_id'] ?>">
            <a class="lite-youtube-fallback" href="https://www.youtube.com/watch?v=<?= $props['video_id'] ?>"><?= $props['description'] ?></a>
        </lite-youtube>
    <?php endif ?>

<?= $el->end() ?>
