<?php

/**
 * Driver for PHP Web Application
 *
 * @package None
 * @author Jete O'Keeffe
 * @license none
 */

// Setup configuration files
$dir = dirname(__DIR__);
$appDir = $dir . '/app';

// Necessary requires to get things going
require $appDir . '/library/utilities/debug/PhpError.php';
require $appDir . '/library/interfaces/IRun.php';
require $appDir . '/library/application/Web.php';

// Necessary paths to autoload & config settings
$configPath = $appDir . '/config/';
$viewsPath = $appDir . '/views/';
$config = $configPath . 'config.php';
$autoLoad = $configPath . 'autoload.php';
$routes = $configPath . 'routes.php';

// Capture runtime errors
register_shutdown_function(['Utilities\Debug\PhpError','runtimeShutdown']);

try {

	$app = new Application\Web();

	// Record any php warnings/errors
	set_error_handler(['Utilities\Debug\PhpError','errorHandler']);

	 // Setup Web App (dependency injector, configuration variables, routes)
        $app->setAutoload($autoLoad, $appDir);
        $app->setConfig($config);
	$app->setRoutes($routes);
	$app->setDebugMode(TRUE);
	$app->setView($viewsPath, $volt = TRUE);
	//$app->setEvents();

	// Boom, Run
	$app->run();

} catch(Exception $e) {
	echo $e;
}
