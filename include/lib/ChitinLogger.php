<?php

/**
 * Accepts text for logging and writes out to a file
 *
 * Configuration Variables:
 * - boolean $config['logger']['enabled'], Default: true for development, false for production
 * - string  $config['logger']['path'], Default: include/logs/$profile.log
 */
class ChitinLogger {
	private static $buffer = '';
	
	/**
	 * Adds text to the log
	 */
	static public function log ($text) {
		self::$buffer .= $text . "\n";
	}
	
	/**
	 * Empty out the contents of the log buffer
	 */
	static public function clear () {
		self::$buffer = '';
	}
	
	/**
	 * Writes out gathered log text to a file
	 * @return boolean Did the buffer get effectively 
	 */
	static public function flush () {
		if (self::_isEnabled()) {
			error_log(self::$buffer . "\n", 3, self::_getLogPath());
			self::clear();
			return true;
		}
			return false;
	}

	/** 
	 * Determine if we should even logging should be enabled, based on if the log is writable and if the configuration is setup appropriately
	 * @return boolean
	 */
	static  function _isEnabled () {
		return (self::_isWritable(self::_getLogPath()) &&
			isset($GLOBALS['config']['logger']['enabled']) && $GLOBALS['config']['logger']['enabled']);
	}
	
	/**
	 * Return the desired log path, regardless of whether it exists or is writable
	 * @return string
	 */
	static  function _getLogPath () {
		if (!isset($GLOBALS['config']['logger']['path']))
			$GLOBALS['config']['logger']['path'] = dirname(dirname(__FILE__)) . "/logs/" . $_ENV['SERVER_ENV'] . ".log";

		return $GLOBALS['config']['logger']['path'];
	}
	
	/**
	 * Determine if the given file path exists and is writable, or if its parent directory exists and is writable
	 * @param string $path
	 * @return boolean
	 */
	static  function _isWritable ($path) {
		/* You would expect this method to use is_writable(), but due to
		 * http://bugs.php.net/bug.php?id=46245 it can incorrectly returns true
		 * over NFS.  '02' is the octal UNIX permission mask for "others, writable"
		 * so ensure that the file is chmod'd xx7.
		 */
		
		if (file_exists($path))
			return !!(02 & fileperms($path));
		else if (is_dir(dirname($path)) && (02 & fileperms(dirname($path)))) {
			touch($path);
			chmod($path, 0777);
			return true;
		} else
			return false;
	}
}

?>
