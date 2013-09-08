<?php

/**
 * @author Jete O'Keeffe
 * @version 1.0
 * @link http://docs.phalconphp.com/en/latest/reference/routing.html
 * @eg.

$routes = [
 	'/uri' => [
		'controller' => 'index',
		'action' => 'cheese'
	],
];

 */

$routes = [
	'/' => [
		'controller' => 'index',
		'action' => 'index'
	],
	'/index' => [
		'controller' => 'index',
		'action' => 'index'
	]
];

return $routes;
