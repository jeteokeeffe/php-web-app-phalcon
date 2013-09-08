<?php

/**
 * Main Application for Web Application
 *
 * @package Application
 * @author Jete O'Keeffe
 * @version 1.0
 * @example
 	$app = new Web();
	$app->setConfig('/path/to/config.php');
	$app->setAutoload('/path/to/autoload.php');
	$app->setRoutes('/path/to/routes.php');
	$app->setView();
	$app->run();
 */

namespace Application;

use Interfaces\IRun as IRun;

class Web extends \Phalcon\Mvc\Application implements IRun {

	/**
	 * @var turn on or off debug mode
	 */
	protected $_debug;

	/**
	 * @var	array of view paths used by script (for debugging)
	 */
	protected $_views;

	/**
	 * simple constructor
	 * 
	 * @param directory of the project
	 */
	public function __construct() {
		$this->_views = array();
		$this->_debug = FALSE;
	}

        /**
         * Set namespaces to tranverse through in the autoloader
         *
         * @link http://docs.phalconphp.com/en/latest/reference/loader.html
         * @throws Exception
         * @param string $file          map of namespace to directories
         */
        public function setAutoload($file, $dir) {
                if (!file_exists($file)) {
                        throw new \Exception('Unable to load autoloader file');
                }

                // Set dir to be used inside include file
                $namespaces = include $file;

                $loader = new \Phalcon\Loader();
                $loader->registerNamespaces($namespaces)->register();
        }

        /**
         * Set Dependency Injector with configuration variables
         *
         * @throws Exception
         * @param string $file          full path to configuration file
         */
        public function setConfig($file) {
                if (!file_exists($file)) {
                        throw new \Exception('Unable to load configuration file');
                }

                $di = new \Phalcon\DI\FactoryDefault();
                $di->set('config', new \Phalcon\Config(include $file));

                $di->set('db', function() use ($di) {
                        return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                                'host' => $di->get('config')->database->host,
                                'username' => $di->get('config')->database->username,
                                'password' => $di->get('config')->database->password,
                                'dbname' => $di->get('config')->database->name
                        ));
                });
                $this->setDI($di);
        }


        /**
         * Set Routes\Handlers for the application
         *
         * @throws Exception
         * @param file                  file thats array of routes to load
         */
	public function setRoutes($file) {
		if (!file_exists($file)) {
                        throw new \Exception('Unable to load routes file');
                }


		$di = $this->getDI();

		// Load Routes
		$routes = include $file;

		// Add Custom Routes
		if (!empty($routes)) {
			$router = new \Phalcon\Mvc\Router();
			$router->setDefaults(['namespace' => 'Controllers']);
			$router->removeExtraSlashes(TRUE);
			foreach($routes as $uri => $route) {
				$router->add($uri,
					$route
				);
			}
			$router->handle();
		} else {
			throw new \Exception('$routes not set properly');
		}

		$di->set('router', $router);
		$this->setDI($di);
	}

	/**
	 * Set View
	 */
	public function setView($dir, $useVolt = FALSE) {
		$view = new \Phalcon\Mvc\View();
		$view->setViewsDir($dir);
		if ($useVolt) {
			$view->registerEngines(['.volt' => 'Phalcon\Mvc\View\Engine\Volt']);
		}

		$di = $this->getDI();
		$di->set('view', $view);

		$this->setDI($di);
	}

	/**
	 * Turn on or off debug mode
	 * 
	 * @param bool $debug
	 */
	public function setDebugMode($debug) {
		$this->_debug = $debug === TRUE ?: FALSE;
	}
	
	/**
	 * Get debug mode
	 * 
	 * @return bool $debug
	 */
	public function getDebugMode() {
		return $this->_debug;
	}


	/**
	 * Initialize all special routing rules
	 */
	protected function registerRoutes() {
		$di = $this->getDI();
		/*$di->set('view', function() {

			$view = new \Phalcon\Mvc\View();
			$view->setViewsDir('../app/views/');

			if ($this->_debug) {
					//	Track Views
				$eventsManager = new \Phalcon\Events\Manager();

				$eventsManager->attach("view", function($event, $view) {
					if ($event->getType() == 'beforeRenderView') {
						$this->_views[] = $view->getActiveRenderPath();
					}
				});
				$view->setEventsManager($eventsManager);
			}

			return $view;
		});*/
                
		$di->set('url', function() {
			$url = new \Phalcon\Mvc\Url();
			$url->setBaseUri('/');
			return $url;
		});

		// Set Dependancy Injector required by Phalcon MVC
		$this->setDI($di);

	}

	/**
	 * launch/run application
	 */
	public function run() {
		try {

			$isCaptureOn = FALSE;

				//	Setup Everything
			$this->registerRoutes();


				//	Capture Method to duplicate requests, used to debug later
			if ($isCaptureOn === TRUE) {
				$capture = CaptureUtility::singleton();
				$capture->capture();
			}

			// Execute MVC and get display
			echo $this->handle()->getContent();
			flush();

				//	Display debug info for development site only
			if ($this->_debug) {
				$this->printDebug();
			}

				//	Capture Method to duplicate requests
			if ($isCaptureOn === TRUE) {
				$capture->save();
			}

		} catch(\Phalcon\Mvc\Dispatcher\Exception $e) {
			header("HTTP/1.0 404 Not Found");

			$view = new \Phalcon\Mvc\View();
			$view->setViewsDir('../app/views/');
			$view->start();
			$view->render("error", "404");
			$view->finish();
			echo $view->getContent();
		}
	}



	/**
	 * display debug information
	 */
	public function printDebug() {

		$dispatcher = $this->getDI()->get('dispatcher');

		$controller = $dispatcher->getControllerName();
		$action = $dispatcher->getActionName();

		//$main = $this->getDI()->get('view')->getMainView();
		//$ = $view->getLayout(); $view->getMainView();
		$now = microtime(TRUE);
		
		$time = $now - $_SERVER['REQUEST_TIME'];

		echo "<style>
		.debug-table td, .debug-table th {
			font-size: 10px;
			margin: 0;
			padding: 0;
		}
		</style>
		<h7>Phalcon</h7>
		<table class='debug-table table table-striped table-condensed'>
			<tr>
				<td>Time</td>
				<td>$time</td>
			</tr>
			<tr>
				<td>Controller</td>
				<td>{$controller}</td>
			</tr>
			<tr>
				<td>Action</td>
				<td>{$action}</td>
			</tr>";

			foreach($this->_views as $view) {
				echo "<tr><td>View</td><td>{$view}</td></tr>";
			}

		echo "</table>";


			//	Print out Session Data
		if (!empty($_SESSION)) {
			echo "<h7>Session</h7>
			<table class='debug-table table table-striped table-condensed'><tr><th>Session Name</th><th>Session Value</th></tr>";
			echo "<tr><td>" . session_name() . "</td><td>" . session_id() . "</td></tr>";
			foreach($_SESSION as $index => $value) {
				echo "<tr><td>$index</td><td>" . printValue($value) . "</td></tr>";
			}
			echo "</table>";
		}

		//printSuperGlobal($_SESSION, "Session");
		printSuperGlobal($_POST, "Post");
		printSuperGlobal($_COOKIE, "Cookie");

		if (class_exists('\\exceptions\Logger', FALSE)) {
			$exceptions = \exceptions\Logger::getInstance()->getAll();
			if (!empty($exceptions)) {
				echo "<h7>Exceptions</h7>
				<table class='table debug-table table-striped table-condensed'><tr><th>Message</th><th>Code</th><th>File</th><th>Line</th></tr>";
				foreach($exceptions as $exception) {
					echo "<tr>
					<td>" . $exception->getMessage() . "</td>
					<td>" . $exception->getCode() . "</td>
					<td>" . $exception->getFile() . "</td>
					<td>" . $exception->getLine() . "</td>
					</tr>";
				}
				echo "</table>";
			}

		}

		$queries = DatabaseFactory::getQueries();
		if (!empty($queries)) {
			echo "<h7>Database</h7>
			<table class='table debug-table table-striped table-condensed'><tr><th>Query</th><th>File</th><th>Line</th><th>Success</th></tr>";
			foreach($queries as $query) {
				echo "<tr>
					<td>{$query->query}</td>
					<td>{$query->file}</td>
					<td>{$query->line}</td>
					<td>{$query->success}</td>
				</tr>";	
			}
			echo "</table>";
		}

		if (class_exists('MemcachedCache', FALSE)) {
			echo "<h7>Memcached</h7>";
			$cache = \MemcachedCache::singleton();
			echo "<table class='table debug-table table-striped table-condensed'>";
			foreach($cache->getServerList() as $server) {
				echo "<tr><td>{$server['host']}</td><td>{$server['port']}</td><td></td></tr>";
			}
			echo "</table>";
		}


			//	Get All CLI Commands
		if (class_exists('\\cli\Execute', FALSE)) {
			$commands = Execute::singleton()->getCommands();
			if (!empty($commands)) {
				echo "<h7>Shell Comamnds</h7>
			<table class='table debug-table table-striped table-condensed'><tr><th>Command</th><th>File</th><th>Line</th><th>Success</th></tr>";

				foreach($commands as $command) {
					$command->toRow();
				}
				echo "</table>";
			}
		}
	}
}
