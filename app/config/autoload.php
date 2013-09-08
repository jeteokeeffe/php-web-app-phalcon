<?php

/**
 * Auto Load Class files by namespace
 *
 * @eg 
 	'namespace' => '/path/to/dir'
 */

$autoload = [
	'Utilities\Debug' => $dir . '/library/utilities/debug/',
	'Application' => $dir . '/library/application/',
	'Interfaces' => $dir . '/library/interfaces/',
	'Controllers' => $dir . '/controllers/',
	'Models' => $dir . '/models/'
];

return $autoload;
