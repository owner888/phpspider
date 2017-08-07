<?php

$GLOBALS['config']['db'] = array(
    'host'  => '127.0.0.1',
    'port'  => 3306,
    'user'  => 'root',
    'pass'  => 'root',
    'name'  => 'demo',
);

$GLOBALS['config']['test'] = array(
    'host'  => '127.0.0.1',
    'port'  => 3306,
    'user'  => 'root',
    'pass'  => 'root',
    'name'  => 'test',
);

$GLOBALS['config']['redis'] = array(
    'host'      => '127.0.0.1',
    'port'      => 6379,
    'pass'      => '',
    'db'        => 5,
    'prefix'    => 'phpspider',
    'timeout'   => 30,
);

$GLOBALS['config']['redis_test'] = array(
    'host'      => '127.0.0.1',
    'port'      => 6379,
    'pass'      => '',
    'db'        => 0,
    'prefix'    => 'test',
    'timeout'   => 30,
);

//include "inc_mimetype.php";
