<?php
/**
 * Controller Dispatcher
 *
 * Ideally these entire file would be encapsulated inside of the Dispatcher
 * class. However, we wanted to include the controllers inside of the global
 * scope, and PHP's include methods do not have a way to force a include into
 * the global if the include() is called within a method
 *
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @copyright Mudbug Media, 2005
 * @version $Id: dispatcher.php 3932 2009-04-28 21:02:38Z seanbug $
 * @package Chitin
 * @subpackage Dispatcher
 */

include_once 'include/init.php';
include_once 'routes.php';
include_once 'include/controllers/ApplicationController.php';

// SCRIPT_NAME holds the post-mod_rewrite file name
define('PATH_TO_DISPATCH_ROOT', dirname($_SERVER['SCRIPT_NAME']));

// This GET variable is passed by mod_rewrite
$path = $_GET['dispatch_original_path'];

ChitinLogger::log(date('c') . ' ' .  $_GET['dispatch_original_path']);
foreach ($routes as $route) {
	if (($coordinates = $route->matchUrl($path)) !== false) {
		$controller_name = preg_replace('/[^A-Za-z0-9-_]/', '', Dispatcher::shortToController($coordinates['controller']));

		/*
		 * Attempt to include the controller
		 */
		// Check that a file for the controller exists
		if (!Dispatcher::controllerFileExists($controller_name))
			continue;

		include_once 'controllers/' . $controller_name . '.php';
		
		// Check that class was properly declared in the file
		if (!class_exists($controller_name))
			continue;
			
		/*
		 * Instantiate the controller and call the action method
		 */
		$controller = new $controller_name();

		// Check that the action exists
		if (!method_exists($controller, $coordinates['action']))
			continue;

		ChitinLogger::log("Route: " . $route->rule);
		ChitinLogger::log("Coordinates: " . var_export($coordinates, true));
		
		// Run the controller and fetch the page
		$controller->start($coordinates);
		$page = $controller->getPage();
		
		ChitinLogger::flush();
		/*
		 * Fetch the $page variable and include the layout
		 */
		if (isset($page['layout']) && $page['layout'] === false)
			echo $page['content'];
		else if (isset($page['layout']) && isset($config['layouts'][$page['layout']]))
			include $config['layouts'][$page['layout']];
		else
			include $config['layouts']['normal'];
		exit(0);
	}
}

// Nothing was found
Dispatcher::send404();
ChitinLogger::flush();

?>