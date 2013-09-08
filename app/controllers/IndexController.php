<?php

/**
 *
 * @package Controllers
 */

namespace Controllers;

class IndexController extends \Phalcon\Mvc\Controller {

	public function indexAction() {
		$this->view->setVar('name', "PHP Web Application using Phalcon MVC");
	}
}
