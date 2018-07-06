<?php
// Duplicates plugin configuration
return [
    'Duplicates' => [
        'path' => CONFIG . 'Modules' . DS,
        'limit' => 2,
        'status' => [
            'default' => 'pending',
            'list' => ['pending', 'processed']
        ]
    ]
];
