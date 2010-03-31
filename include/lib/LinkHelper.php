<?php
/**
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @copyright Mudbug Media, 2007-07-18
 * @version $Id: LinkHelper.php 2583 2008-11-20 16:23:19Z gabebug $
 * @package Chitin
 * @subpackage Helpers
 */

/**
 * Iteratively returns progressive elements in an array
 * @package Chitin
 * @subpackage Helpers
 */
class LinkHelper {

	/**
	 * Returns an absolute path for a given URL.
	 *
	 * If a URI is provided (with '://'), the passed URI will be returned unmodified.
	 * @uses PathToRoot
	 * @return string
	 */
	function url ($params = null) {
		$url = (is_array($params)) ? $params['url'] : $params;

		if (is_null($url))
			return ((isset($_SERVER['REDIRECT_URL'])) ? $_SERVER['REDIRECT_URL'] : $_SERVER['SCRIPT_NAME']);

		if (isset($url[0]) && (strpos($url, '://') !== false || $url[0] == '/')) {
			return $url;
		}

		include_once 'lib/PathToRoot.php';
		return PathToRoot::getAbsolute() . $url;
	}
	
	
	/**
	 * Given a URL Path, returns the offset by trimming the PathToRoot
	 *
	 * If the provided URL is not within the PathToRoot, return the URL un-modified
	 *
	 * Example:
	 * If PathToRoot is /a/b/, LinkHelper::offset("/a/b/c/d.php") returns "c/d.php"
	 *
	 * @uses PathToRoot
	 * @param mixed $params A string URL, or An array of parameters:
	 * - string 'url' Path component of URL, e.g. "/a/b/c/d.php";, or 
	 * - string 'path_to_root' [Optional] Hard-coded absolute path to the site root. Default: PathToRoot::getAbsolute();
	 * @return string
	 */
	function offset ($params = null) {
		$url = (is_array($params)) ? (isset($params['url']) ? $params['url'] : null) : $params;
		
		if (is_null($url))
			$url = ((isset($_SERVER['REDIRECT_URL'])) ? $_SERVER['REDIRECT_URL'] : $_SERVER['SCRIPT_NAME']);
		
		$trim = isset($params['path_to_root']) ? $params['path_to_root'] : PathToRoot::getAbsolute();
		
		// PathToRoot did not begin the path, return the URL un-modified
		if (strpos($url, $trim) !== 0)
			return $url;
		
		return substr($url, strlen($trim));
	}
	
	/**
	 * Return a URL for displaying the current page sorted by a particular column
	 *
	 * Params
	 * - sort_field
	 * - sort_order
	 *
	 * @uses ChitinUrl
	 * @param mixed $params
	 * @return string URL
	 */
	function column ($params) {
		include_once 'lib/ChitinUrl.php';
		
		$url = new ChitinUrl();
		if (isset($params['sort_field']))
			$url->setVar('sort_field', $params['sort_field']);

		if (!is_null($current_order = $url->getVar('sort_order')))
			$url->setVar('sort_order', (strtolower($current_order) == 'asc') ? 'desc' : 'asc');
		else
			$url->setVar('sort_order', (isset($params['sort_order']) ? $params['sort_order'] : 'asc'));
		
		return $url->get();
	}
	

}
?>