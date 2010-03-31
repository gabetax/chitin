<?php
/**
 * Dispatcher
 *
 * @author Gabe Martin-Dempesy
 * @version $Id: Dispatcher.php 3932 2009-04-28 21:02:38Z seanbug $
 * @copyright Mudbug Media, 2007-04-18
 * @package Chitin
 * @subpackage Dispatcher
 */


/**
 * Convert between URL's and routes, and instantiates Controllers
 * @package Chitin
 * @subpackage Dispatcher
 */
class Dispatcher {

	function controllerFileExists ($controller_name) {
		return ChitinFunctions::file_exists_in_include_path('controllers/' . $controller_name . '.php');
	}
	
	/**
	 * Fetch a URL for given coordinates
	 * 
	 * Examples:
	 * - UserAddController       => user/add
	 * - AccountCreditcardAdd    => account/creditcard/add
	 * - AdminController         => admin
	 *
	 * @param string $controller_name
	 * @return string Directory-style path
	 */
	function controllerToShort ($controller_name) {
		
		$path = preg_replace(
			array(
			'/Controller$/',
			'/([A-Z])/',
			'/^\//'
			),

			array(
			'',
			'/${1}',
			''
			),

			$controller_name
		);
		
		return strtolower($path);
	}
	
	/**
	 * Fetch coordinates for a URL
	 *
	 * @param string $path
	 * @return array Coordinates
	 */
	function shortToController ($path) {
		$controller_name = str_replace('/', ' ', $path);
		$controller_name = ucwords($controller_name);
		$controller_name = str_replace(' ','', $controller_name);
		$controller_name .= 'Controller';
		return $controller_name;
	}
	
	/**
	 * Sends an Apache-style '404' error message to the browser and exits
	 *
	 * @return void
	 */
	function send404 () {
		header("HTTP/1.0 404 Not Found");
		
		if (!isset($GLOBALS['config']['errordocument']['404']) || strlen($GLOBALS['config']['errordocument']['404']) < 1) $GLOBALS['config']['errordocument']['404'] = '404.php';
		
		$path = ($GLOBALS['config']['errordocument']['404'][0] == '/') ?
			$_SERVER['DOCUMENT_ROOT'] . $GLOBALS['config']['errordocument']['404'] : // Absolute paths are from the DOCUMENT_ROOT
			dirname(__FILE__) . '/../../' . $GLOBALS['config']['errordocument']['404']; // Relative paths are from include/'s parent
		
		if (file_exists($path)) {
			ChitinLogger::log(__CLASS__ . '::' . __FUNCTION__ . ": Could not locate a matching route for URL, sending 404 document in '$path'");
			include_once $path;
			echo "\n<!-- Chitin -->";
		} else {
			ChitinLogger::log(__CLASS__ . '::' . __FUNCTION__ . ": Could not locate custom document in '$path', using internal.");
	echo <<<EOF
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<!-- Chitin -->
<h1>Not Found</h1>
<p>The requested URL {$_SERVER['REQUEST_URI']} was not found on this server.</p>
<hr>
{$_SERVER['SERVER_SIGNATURE']}
</body></html>
EOF;
		}
	ChitinLogger::flush();
	die();
	} 
	
}



/**
 * An instance of a ChitinRoute represents a mapping of a string based patttern
 * to a controller and action of that controller.
 * @package Chitin
 * @subpackage Dispatcher
 */
class ChitinRoute {
	
	var $rule; // tracked for logging purposes
	var $compontents;
	var $defaults;
	
	/**
	 * @var string $rule URL like path to match against, e.g. ':controller/:action/:id'
	 * @var array $defaults associative array of components that will default the ones pulled from $rule
	 * @var array $regexs associative array of regular expressions that a given component name must match
	 */
	function ChitinRoute ($rule, $defaults = array(), $regexs = array()) {
		$this->rule = $rule;
		$components = explode('/', $rule);
		$this->defaults = $defaults;
		$this->components = array();
		foreach ($components as $component) {

			// Checking for an empty component protects against $rule == '' or $rule == ":controller//:action"
			if ($component != '') {
				// needs to strip leading :, and check to see if element exists in arrays
				if ($component[0] == ':') {
					$component = substr($component, 1);
					$dynamic = true;
					$regex = (isset($regexs[$component]) ? $regexs[$component] : null);
				} else {
					$regex = $component;
					$component = null;
					$dynamic = false;
				}
				$this->components[] = new ChitinRouteComponent($component, $regex, $dynamic);
			}
		}
	}
	
	/**
	 * Create a regexp that matches and captures each component of this route
	 * in order.
	 *
	 * @access private
	 * @return string
	 */
	function _toRegex () {
		$patterns = array();
		
		$i = count($this->components);
		// While there are elements to process, and this is the first element *or* the trailing component is optional..
		while ($i >= 0 && ($i == count($this->components) || isset($this->defaults[$this->components[$i]->name]))) {
			$patterns[] = $this->_subToRegex($i);
			$i--;
		}
		return '/' . join('|', $patterns) . '/';
	}

	/**
	 * Create a regular expression matching components from 0 to $stop
	 *
	 * This function is used by _toRegex to create progressively shorter sub
	 * expressions which are |'d together, to allow for trailing components to
	 * be optional
	 *
	 * @access private
	 * @param integer $stop
	 * @return string regular expression, without wrapping //'s
	 */
	function _subToRegex ($stop) {
		$patterns = array();
		for ($i = 0; $i < $stop; $i++) {
			// Don't capture non-dynamic components
			if ($this->components[$i]->dynamic)
				$patterns[] = '('. $this->components[$i]->regex .')';
			else
				$patterns[] = $this->components[$i]->regex;
		}
		// (?: ) is a "non-capturing" grouping
		return "(?:^" . implode('\/', $patterns) . "$)";
	}
	
	
	/**
	 * Determine if this particular route matches a given URL
	 * @param string $url URL relative to the dispatcher
	 * @return coordinates or false
	 */
	function matchURL ($url) {
		$ret = $this->defaults;
		if (preg_match($this->_toRegex(), $url, $matches)) {
			/* Due to having optional parameters, each index of $matches may
			 * not be what you're looking for.  We assume that every component
			 * will have a non-empty match, and then we simply ignore the empty
			 * matches */
			for ($i = 1, $c = 0; $i < count($matches); $i++) {
				if ($matches[$i] != '') {
					// Skip all the static components -- those do not get captured
					while ($this->components[$c]->dynamic == false) $c++;
					$this->components[$c]->value = $matches[$i];
					$ret[$this->components[$c]->name] = $matches[$i];
					$c++;
				}
			}
		} else {
			return false;
		}

		return $ret;
	}
	

	/**
	 * Determine if the given coordinate matches this rule
	 *
	 * This function will return false if the coordinate contains any component
	 * that isn't in the route, OR if the route contains any coordinate that
	 * isn't in the coordinate.
	 *
	 * @param array $coordinate
	 * @return string URL or false
	 */
	function matchCoordinates ($coordinate) {
		$url = array();
		// Ensure this route has all the same components as the coordinate, and that the regexp's match
		foreach ($coordinate as $name => $value)
			if (!$this->hasComponent($name, $value) && (!isset($this->defaults[$name]) || $this->defaults[$name] != $value))
				return false;
				
		foreach ($this->components as $c)
			// Ensure the component was specified in the coordinate
			if ($c->dynamic) {
				if (!isset($coordinate[$c->name]))
					return false;
				else
					$url[] = $coordinate[$c->name];
			} else
				$url[] = $c->regex;
		

		return join('/', $url);
	}
	
	/**
	 * Determine if this route contains a component $name, and if the provide
	 * value matches the regex
	 *
	 * @param $name Name of the component (e.g. 'controller', 'action')
	 * @param $value Value of the component to regexp
	 * @return boolean
	 */
	function hasComponent ($name, $value = null) {
		foreach ($this->components as $c)
			if ($c->name == $name) {
				if (!is_null($value)) {
					return (preg_match("/{$c->regex}/", $value) > 0);
				} else
					// We have a name match, and don't care about regexp's
					return true;
			}
		return false;
	}
}

/*
 * One component of the overall route / coordinate
 * @package Chitin
 * @subpackage Dispatcher
 */
class ChitinRouteComponent {
	var $name;
	var $regex;
	var $value;
	var $dynamic; // Did the name originally begin with a ":" ?

	function ChitinRouteComponent ($name, $regex = null, $dynamic) {
		$this->name = $name;
		$this->value = null;
		$this->regex = (!is_null($regex) ?
			$regex :
			// By default Controllers will be greedy, allowing URLs like admin/user/index for AdminUserController::index()
			(($name == 'controller') ? '.+' : '.+?'));
		$this->dynamic = $dynamic;
		
	}
}


?>