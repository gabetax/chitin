<?php
/**
 * Site-wide initialization routines for inclusion on all dynamic pages
 *
 * - Alter the include_path to be set to the directory this file is in
 * - Setup the __autoload method
 * - Ensures magic quotes are disabled
 * - Include the configuration array, $config
 * - Sets the system-wide timezone if set in $config['time_zone']
 * - Include the profile for the server environment
 * - Load plugins
 *
 * @author    Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @copyright Copyright &copy; 2005 Mudbug Media
 * @package   Chitin
 * @version   $Id: init.php 2977 2008-12-12 14:41:09Z lukebug $
 */

// This file must stay in the root of the include directory
// Note, we are using ini_[gs]et instead of [gs]et_include_path as it is more portable
ini_set('include_path', dirname(__FILE__) . PATH_SEPARATOR .  ini_get('include_path'));

// Typically this would be set in your Apache configuration.  If nothing is there, assume we're production for safety
if (!isset($_ENV['SERVER_ENV']))
	$_ENV['SERVER_ENV'] = 'production';

// Convert all errors to ErrorException and log them.  Warnings and Notices are not thrown, and are instead dumped to the screen if we are not in production mode
include_once 'lib/ChitinErrorHandler.php';
set_error_handler('ChitinErrorHandler', error_reporting());

include_once 'lib/__autoload.php';
include_once 'lib/magicquotes.disable.php';
include_once 'config.php';

// Configure Timezone
if (isset($config['time_zone'])) {
	if (function_exists('date_default_timezone_set'))
		date_default_timezone_set($config['time_zone']);
	putenv("TZ=" . $config['time_zone']);
}

// Include server profile if available, e.g. 'include/profiles/development.php'
if (file_exists(dirname(__FILE__) . '/profiles/' . $_ENV['SERVER_ENV'] . '.php'))
	include 'profiles/' . $_ENV['SERVER_ENV'] . '.php';

// Load Plugins (closing PHP tag intentionally left off for "phpintern plugin" appending)
// ChitinPlugin::load('example');
