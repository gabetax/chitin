<?php
/**
 * Path to Root functions
 *
 * Date Created: 2004-02-26
 *
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @copyright Mudbug Media, 2005
 * @version $Id: PathToRoot.php 2763 2008-12-01 23:16:44Z lukebug $
 * @package Chitin
 *
 * Changelog
 * - 2007-06-13: Added PHP5 support (failover for no PATH_TRANSLATED var). (Gabe Martin-Dempesy)
 * - 2007-06-12: Added support for Windows. (Luke Ledet)
 * - 2006-06-21: added support for files within a mod_alias or mod_userdir.
 *   This support does not work when using mod_rewrite in conjunction with
 *   these modules (Gabe Martin-Dempesy)
 * - 2005-12-08: fixed incorrect path finding behavior in getAbsolute() that
 *   occurred whenever $_SERVER['REDIRECT_URL'] was a folder name. (Sean McCann)
 * - 2005-11-30: Allowed the filename to search for to be specified as an argument
 * - 2005-11-29: Converted to static functions in a class, moved caching to
 *   static variables, and rewrote getRelative to use values from getAbsolute
 *   (Gabe Martin-Dempesy)
 * - 2004-02-26: Initial file creation (Gabe Martin-Dempesy)
 */

/**
 * Provide directory paths from the presently viewed file to a defined root
 * directory of the site.
 *
 * This root directory is defined with the presence of a dummy file named
 * SITE_ROOT being present in a directory.  This allows you to setup a
 * smaller site within a sub directory of a domain, as not all sites exist
 * exclusively in the DOCUMENT_ROOT.
 *
 * If no SITE_ROOT file exists within the domain's DOCUMENT_ROOT, these
 * functions will return the path to the DOCUMENT_ROOT by default.
 *
 * @package Chitin
 * @link https://wiki.mudbugmedia.com/index.php/PathToRoot
 */
class PathToRoot {
	/**
	 * Constructs an <b>absolute</b> path to the root of the site.
	 *
	 * @param string $site_root_file Name of the file to find the path to
	 * @return string absolute path to the root of the site, in form of '/foo/bar/'
	 */
	function getAbsolute ($site_root_file = 'SITE_ROOT') {

		// Configuration
		static $paths = array();

		if (!isset($paths[$site_root_file])) {

			// Handle mod_rewrite URL's
			$SCRIPT_NAME = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['SCRIPT_NAME'];

			// Set DOCUMENT_ROOT if it isn't aleady (useful on Windows)
			if (!isset($_SERVER['DOCUMENT_ROOT']))
				$_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr($_SERVER['PATH_TRANSLATED'], 0, 0-strlen($_SERVER['PHP_SELF'])));
			
			// Force a trailing slash on $docroot
			if (substr($_SERVER['DOCUMENT_ROOT'], -1) != '/')
				$docroot = $_SERVER['DOCUMENT_ROOT'] . '/';
			else
				$docroot = $_SERVER['DOCUMENT_ROOT'];

			// Find the folder where the currently running script
			// resides.  If the $SCRIPT_NAME is already a directory,
			// then don't do anything else.  Note that is_dir() is
			// not used since we may be using mod_rewrite to route
			// requests for non-existant folders elsewhere.  In those
			// cases, $SCRIPT_NAME would look like a folder, but would
			// not actually exist, causing is_dir() to fail.  Instead,
			// we simply look for a trailing '/'.
			$paths[$site_root_file] = (substr($SCRIPT_NAME, -1, 1) == '/') ? $SCRIPT_NAME : dirname($SCRIPT_NAME);

			// For cases where the file might be outside of the DOCUMENT_ROOT,
			// as is the case for mod_alias and mod_userdir, we will also scan
			// for SITE_ROOT while popping off PATH_TRANSLATED
			$path_translated = isset($_SERVER['PATH_TRANSLATED']) ? dirname($_SERVER['PATH_TRANSLATED']) : dirname($_SERVER['SCRIPT_FILENAME']);


			// Look for the $site_root_file.  Start in our present folder,
			// and work our way up the directory tree.  Stop if we get back
			// to the root folder '/' without finding the $site_root_file.
			while ($paths[$site_root_file] != '/' && // no SITE_ROOT specified
				   $paths[$site_root_file] != '.' && // no slashes were in SCRIPT_URL, should not happen
				   $paths[$site_root_file] != '' &&  // same as above, but for PHP < 4.0.3
				   $paths[$site_root_file] != '\\' && // dirname('/foo') on windows returns \
				   !file_exists($docroot . $paths[$site_root_file] . '/' . $site_root_file) &&
				   (!file_exists($path_translated . '/' . $site_root_file) || isset($_SERVER['REDIRECT_URL']))) {
				$paths[$site_root_file] = dirname($paths[$site_root_file]);
				$path_translated = dirname($path_translated);
			}

			// dirname('/foo') on windows returns \
			if ($paths[$site_root_file] == '\\')
				$paths[$site_root_file] = '/';

			// append a trailing slash  if necessary
			if (substr($paths[$site_root_file], -1) != '/') {
				$paths[$site_root_file] .= '/';
			}
		}

		return $paths[$site_root_file];
	}

	/**
	 * Constructs a <b>relative</b> path from the current directory of SCRIPT_FILENAME to the root of the website.
	 *
	 * @param string $site_root_file Name of the file to find the path to
	 * @return string relative path to the root of the site, in form of '(../)*'
	 */
	function getRelative ($site_root_file = 'SITE_ROOT') {

		static $paths = array();

		if (!isset($paths[$site_root_file])) {

			// Handle mod_rewrite URL's
			$SCRIPT_NAME = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['SCRIPT_NAME'];

			// If the request is on a file, strip off the filename to the ending slash.
			// Note that dirname('/some/directory/') returns '/some'

			if (substr($SCRIPT_NAME, -1, 1) == '/')
				$script_filepath = $SCRIPT_NAME;
			else
				$script_filepath = dirname($SCRIPT_NAME) . '/';

			// dirname('/index.php') returns '/' -- it has a slash at the end.  Trim it.
			if ($script_filepath == '//')
				$script_filepath = '/';

			// Integers, how many directories deep are we from '/'
			$pwd_depth = substr_count($script_filepath, '/');
			$root_depth = substr_count(PathToRoot::getAbsolute($site_root_file), '/');

			$directory_depth = $pwd_depth - $root_depth;

			$paths[$site_root_file] = '';
			for ($i = 0; $i < $directory_depth; $i++) {
				$paths[$site_root_file] .= '../';
			}

		}

		return $paths[$site_root_file];
	}

	/**
	 * Alias to getAbsolute
	 * @param string $site_root_file Name of the file to find the path to
	 * @return string
	 */
	function get ($site_root_file = 'SITE_ROOT') {
		return PathToRoot::getAbsolute($site_root_file);
	}

}

?>