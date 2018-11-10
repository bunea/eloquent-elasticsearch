<?php

return [
    'default' => 'default',
    'connections' => [
        'default' => [
            'hosts' => env('ELASTICSEARCH_HOSTS', '127.0.0.1:9200')
        ]
    ]
];
