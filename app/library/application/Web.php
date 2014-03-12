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
            $type = strtolower($di->get('config')->database->adapter);
            $creds = array(
                'host' => $di->get('config')->database->host,
                'username' => $di->get('config')->database->username,
                'password' => $di->get('config')->database->password,
                'dbname' => $di->get('config')->database->name
            );

            if ($di->get('config')->app->debug) {

                $di->set('profiler', function() {
                    return new \Phalcon\Db\Profiler();
                }, TRUE);

                $event = new \Phalcon\Events\Manager();

                $profiler = $di->getProfiler();

                $event->attach('db', function($event, $connection) use ($profiler) {
                    if ($event->getType() == 'beforeQuery') {
                        $profiler->startProfile($connection->getSQLStatement());
                    }

                    if ($event->getType() == 'afterQuery') {
                        $profiler->stopProfile();
                    }
                });
            } else {
                $event = new \Events\Database\Profile();
            }

            if ($type == 'mysql') {
                $connection =  new \Phalcon\Db\Adapter\Pdo\Mysql($creds);
            } else if ($type == 'postgres') {
                $connection =  new \Phalcon\Db\Adapter\Pdo\Postgesql($creds);
            } else if ($type == 'sqlite') {
                $connection =  new \Phalcon\Db\Adapter\Pdo\Sqlite($creds);
            } else {
                throw new Exception('Bad Database Adapter');
            }

            $connection->setEventsManager($event);

            return $connection;
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
		$di = $this->getDI();

		$view = new \Phalcon\Mvc\View();
		$view->setViewsDir($dir);
		if ($useVolt) {
			$view->registerEngines(['.volt' => 'Phalcon\Mvc\View\Engine\Volt']);
		}

        if ($di->get('config')->app->debug) {
            // Get the views for debugging display
            $eventsManager = new \Phalcon\Events\Manager();
            $eventsManager->attach("view", function($event, $view) {
                if ($event->getType() == 'beforeRenderView') {
                    $this->_views[] = $view->getActiveRenderPath();
                }
            });
            $view->setEventsManager($eventsManager);
        }

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
            $di = $this->getDI();
 

				//	Setup Everything
			//$this->registerRoutes();

			// Execute MVC and get display
			echo $this->handle()->getContent();
			flush();

				//	Display debug info for development site only
			if ($di->get('config')->app->debug) {
				$this->printDebug();
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

        $view = new \Phalcon\Mvc\View\Simple();
        $view->setViewsDir('../app/views/');
        // Render app/views/debug.phtml
        echo $view->render("debug", array(
            'controller' => $controller,
            'action' => $action,
            'views' => $this->_views,
            'time' => microtime(TRUE) - $_SERVER['REQUEST_TIME'],
        ));
	}
}
