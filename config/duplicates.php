<?php
// Duplicates plugin configuration
return [
    'Duplicates' => [
        'path' => CONFIG . 'Modules' . DS,
        'status' => [
            'default' => 'pending',
            'list' => ['pending', 'processed']
        ]
    ]
];
