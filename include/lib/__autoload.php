<?php
/**
 * __autoload() function to automatically include classes
 *
 * Searches the following paths
 * - models/, if class name contains 'Model'
 * - lib/
 *
 * @version $Id: __autoload.php 686 2008-01-16 16:06:04Z gabebug $
 * @copyright Mudbug Media, 2007-08-07
 * @package Chitin
 * @link http://php.net/__autoload
 */
function __autoload ($class_name) {
	$include_path = dirname(dirname(__FILE__));
	if (strpos($class_name, 'Model') !== false && file_exists($include_path . '/models/' . $class_name . '.php')) {
		require_once $include_path . '/models/' . $class_name . '.php';
	} else if (file_exists($include_path . '/lib/' . $class_name . '.php')) {
		require_once $include_path . '/lib/' . $class_name . '.php';
	}
}

?>