<?php
use Cake\Core\Configure;

// get app level config
$config = Configure::read('Duplicates');
$config = $config ? $config : [];

// load default plugin config
Configure::load('Qobo/Duplicates.duplicates');

// overwrite default plugin config by app level config
Configure::write('Duplicates', array_replace_recursive(
    Configure::read('Duplicates'),
    $config
));
