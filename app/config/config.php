<?php

/**
 * Settings to be stored in dependency injector
 */

return [
	'database' => array(
		'adapter' => 'Mysql',
		'host' => 'localhost',
		'username' => 'test',
		'password' => 'test',
		'name' => 'api',
		'port' => 3306
    ),
    'app' => array(
        'debug' => FALSE
    )
];

