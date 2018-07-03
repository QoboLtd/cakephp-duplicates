<?php
use Cake\Routing\Router;

Router::plugin(
    'Qobo/Duplicates',
    ['path' => '/duplicates'],
    function ($routes) {
        $routes->fallbacks('DashedRoute');
    }
);
