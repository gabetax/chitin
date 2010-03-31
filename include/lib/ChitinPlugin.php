<?php
/**
 * ChitinPlugin loader
 *
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @version $Id: ChitinPlugin.php 2707 2008-11-26 18:17:13Z lukebug $
 * @copyright Mudbug Media, 2007-10-24
 * @package Chitin
 **/


/**
 * Loads plugins into a Chitin application
 *
 * @package Chitin
 */
class ChitinPlugin {
	
	static private $loaded = array();
	
	/**
	 * Returns the disc path for a given plugin inside the 'plugins' folder
	 * @param string $name Name of plugin
	 * @return string
	 */
	static public function getPath ($name) {
		return dirname(dirname(__FILE__)) . '/plugins/' . $name;
	}
	
	/**
	 * Load a plugin
	 *
	 * - Verifies that the plugin files exist, and throws an Exception otherwise
	 * - Appends plugins/$name/ to include_path, after the 'include/'
	 * - Attempt to include plugins/$name/init.php if present
	 * - Attempt to include plugins/$name/routes.php if present, and merge with
	 *   existing routing rules.
	 *
	 * @param string Name of plugin to load, based on directory name in 'plugins/' folder
	 * @throws Exception
	 * @return boolean was plugin found?
	 */
	static public function load ($name)
	{
		if (self::isLoaded($name))
			return true;
		
		// Verifies that the plugin files exist, and throws an Exception otherwise
		$path = self::getPath($name);
		if (!is_dir($path)) {
			throw new Exception('ChitinPlugin::load: could not locate ' . $name);
			return false;
		}
		
		// Inserts plugins/$name/ to include_path, into the second position
		self::setupIncludePath($path);
		
		// Attempt to include plugins/$name/init.php if present
		if (file_exists($path . '/init.php')) {
			include_once $path . '/init.php';
		}
			
		// Attempt to include plugins/$name/routes.php if present
		if (file_exists($path . '/routes.php')) {
			include_once $path . '/routes.php';
			
			if (!isset($GLOBALS['routes'])) $GLOBALS['routes'] = array();
			$GLOBALS['routes'] = array_merge($routes, $GLOBALS['routes']);
		}
		
		self::$loaded[] = $name;
		
		return true;
	}
	
	/**
	 * Adds the path to the second position in the include path
	 *
	 * This allows new include paths not to override the first position, which is the global include
	 *
	 * @access private
	 * @param string $path Path to add
	 */
	private static function setupIncludePath ($path) {
		$parts = explode(PATH_SEPARATOR, ini_get('include_path'));
		ini_set('include_path', $parts[0] . PATH_SEPARATOR . $path . PATH_SEPARATOR . implode(PATH_SEPARATOR, array_slice($parts, 1)));
	}
	
	/**
	 * Returns whether or not a specific plugin is loaded
	 *
	 * @return boolean
	 */
	public static function isLoaded ($name) {
		return in_array($name, self::$loaded);
	}
}

?>
