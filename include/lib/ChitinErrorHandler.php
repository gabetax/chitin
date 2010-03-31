<?php
/**
 * Chitin Error Handler
 *
 * @author Gabe Martin-Dempesy
 * @version $Id: ChitinErrorHandler.php 2375 2008-10-22 19:29:16Z gabebug $
 * @copyright Mudbug Media, 2007-07-09
 * @package Chitin
 */

/**
 * Error handler target that converts errors to ErrorExceptions, and logs them.
 *
 * Non-critical warnings are not thrown, but simply displayed to the screen as long as we are not in production mode
 *
 * @throws ErrorException
 */
function ChitinErrorHandler ($errno, $errstr, $errfile, $errline, $errcontext) {
	$e =  new ErrorException($errstr, 0, $errno, $errfile, $errline);
	error_log($e);
	
	switch ($errno) {
		case E_STRICT:
			// Fuck E_STRICT. Seriously.
			break;
		case E_NOTICE:
		case E_WARNING:
		case E_USER_NOTICE:
		case E_USER_WARNING:
			// Don't even 
			if ($_ENV['SERVER_ENV'] == 'production')
				break;
		
		// These are unworthy of throwing an exception - just send the alert
		if (ini_get('error_reporting') & $errno)
			echo "<pre class=\"error\">$e</pre>\n";
		break;
			
		default:
			throw $e;
	}
}

?>