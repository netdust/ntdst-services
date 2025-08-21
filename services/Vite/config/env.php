<?php

return [
    'output_dir' => $_ENV['VITE_OUTPUT_DIR']??'',
    'wp_enqueue_id' => $_ENV['WP_ENQUEUE_ID']??'',
    'protocol' => $_ENV['VITE_PROTOCOL']??'',
    'host' => $_ENV['VITE_HOST']??'',
    'port' => $_ENV['VITE_PORT']??'',
    'entry_point' => $_ENV['VITE_ENTRY_POINT']??'',
    'node_env' => $_ENV['NODE_ENV']??'',
];