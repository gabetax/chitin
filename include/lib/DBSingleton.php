<?php
/**
 * DB Singleton
 *
 * Date Created:  2005-10-19
 *
 * @author    Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @copyright Copyright &copy; 2005 Mudbug Media
 * @package Chitin
 * @subpackage Singletons
 * @version $Id: DBSingleton.php 1608 2008-06-11 19:44:15Z gabebug $
 *
 * Changelog
 * - 2007-04-10: Removed return by reference recommended by PHP manual (Gabe Martin-Dempesy)
 * - 2005-10-19: Initial file creation (Gabe Martin-Dempesy)
 */


require_once 'DB.php';

class DBException extends Exception { }
class DBConfigException extends DBException { }
class DBConnectException extends DBException { }

/**
 * Singleton object to return an instance of a PEAR DB object
 *
 * Configuration
 * - string $config['dsn'] Sets the Database DSN to connect to, e.g. mysqli://username:password@hostname/database_name
 * - string $config['time_zone'] Sets the Timezone for this MySQL connection
 *
 * @package Chitin
 * @subpackage Singletons
 */
class DBSingleton {
	
	/**
	 * Creates and maintains access to a DB object.
	 *
	 * The DSN used to connect is accessed from $GLOBALS['config']['dsn']
	 *
	 * @static
	 * @return DB upon success, false otherwise.
	 */
	function getInstance () {
		
		static $db;
		
		if (!isset($db)) {
			if (!isset($GLOBALS['config']['dsn']))
				throw new DBConfigException("No database connection information is specified in config['dsn']");
			
			try  {
				$db = DB::connect($GLOBALS['config']['dsn']);
				if (PEAR::isError($db))
					throw new DBConnectException($db->getUserInfo());
					
			// We also need to catch ErrorException incase the error handler is enabled
			} catch (ErrorException $e) {
				throw new DBConnectException($e->getMessage());
			}
			
			$db->setFetchMode(DB_FETCHMODE_ASSOC);
			
			// @see http://dev.mysql.com/doc/refman/5.0/en/time-zone-support.html
			if (isset($GLOBALS['config']['time_zone'])) {
				$result = $db->query("SET time_zone = '{$GLOBALS['config']['time_zone']}'");
				if (PEAR::isError($result))
					throw new DBException("Could not set MySQL time zone: " . $db->getUserInfo());
			}
		}
		
		return $db;
	}
}

?>