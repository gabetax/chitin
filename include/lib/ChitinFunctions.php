<?php

/**
 * Collection of utility functions under a class-based namespace
 */
class ChitinFunctions {
	
	/**
	 * Determine if a given relative path exists within any of the include directories
	 * @param string $filename Relative file name, e.g. "HTTP/Header.php"
	 * @return boolean Does this file exist?
	 */
	static public function file_exists_in_include_path ($filename) {
		foreach (explode(PATH_SEPARATOR, ini_get('include_path')) as $path) {
			if (file_exists($path . '/' . $filename)) {
				return true;
			}
		}
		return false;
	}
}