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
	
	
	/**
	 * Determines whether or not a link matches the current URL
	 *
	 * <a href="blah/" class="<?php if LinkHelper::isCurrent("blah/") echo 'current'">Blah</a>
	 *
	 * @param mixed $params A string URL to be tested (same as 'path_to_test'), or An array of parameters:
	 * - string  current_url    - the URL of the current page.  isCurrent() will test a path against this URL
	 * -   bool  decode_url?    - if true, the current_url is passed through urldecode()
	 * -   bool  match_folders? - if true, tests if the path_to_test is one of the folders in the current URL
	 * - string  path_to_test   - the relative path that may or may not be part of the current URL
	 * @return string boolean
	 */
	function isCurrent ($params) {
		$default_options = array(
			"current_url" => (isset($_SERVER['REDIRECT_URL'])) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'],
			"decode_url?" => true,
			"match_folders?" => true,
			"path_to_test" => '/',
			"match_whole" => false,
		);

		// Merge passed parameters with the defaults.  If only a string is passed, assume
		// that it is the path which we want to test is current
		$options = array_merge($default_options, (is_array($params)) ? $params : array('path_to_test' => (string) $params));

		// Trim the current URL, just in case.  Optionally decode any '%' escape sequences
		$options['current_url'] = trim( ($options['decode_url?']) ? urldecode($options['current_url']) : $options['current_url']);

		// Check whether or not the path to test exactly matches the current URL.
		// This is useful for checking the home-page URL, without subdirectories,
		// for which the standard regex comparison below doesn't work.
		if ($options['match_whole'])
			return ($options['current_url'] == $options['path_to_test']);

		// Figure out whether or not 'path_to_test' is in 'current_url'.  substr() is faster than preg_match(), so we'll use
		// substr() for our basic case: a filename appearing at the end of the current_url.  Folder names can appear anywhere
		// in current_url, so we will need to match against a pattern that tests for folder names appearing at the beginning of
		// the URL or immediately after a /.  Our pattern assumes that there will be something in the URL after the folder name
		// as well (our substr() match will catch any folder names appearing at the end of the URL).  Folder matching via
		// preg_match() will only run if 'match_folders?' is true, and if the path_to_test looks like a folder
		$pattern = "/\/" . preg_quote($options['path_to_test'], '/') . ".+/";
		return ((substr($options['current_url'], -1 * strlen($options['path_to_test'])) === $options['path_to_test']) || (
			$options['match_folders?'] && 
			($options['path_to_test'][strlen($options['path_to_test']) - 1] == '/') &&
			preg_match($pattern, $options['current_url']))
		) ? true : false;
	}


	/**
	 * Prints a class name or class attribute if a link matches the current URL
	 * 
	 * <a href="blah/" <?php echo LinkHelper::markCurrent("blah/");?>>Blah</a>
	 *
	 * @param mixed $params A string URL to be tested (same as 'path_to_test'), or An array of parameters:
	 * - string  class - the name of the class to be used if the path_to_test matches the current URL
	 * - string  class_attribute_wrapper - a formatted string to wrap the class name if print_class_attribute? is set
	 * - string  path_to_test   - the relative path that may or may not be part of the current URL
	 * -   bool  print_class_attribute?    - if true (default), the entire class attribute (e.g. class="some-class-name") is returned
	 * @return string boolean
	 */
	function markCurrent ($params) {
		$default_options = array(
			"class" => 'current',
			"class_attribute_wrapper" => ' class="%s"',
			"path_to_test" => '',
			"print_class_attribute?" => true,
			"match_whole" => false,
		);

		// Merge passed parameters with the defaults.  If only a string is passed, assume
		// that it is the path which we want to test is current
		$options = array_merge($default_options, (is_array($params)) ? $params : array('path_to_test' => (string) $params));

		return (LinkHelper::isCurrent($options)) ? 
			($options['print_class_attribute?']) ? 
				sprintf($options['class_attribute_wrapper'], $options['class']) : $options['class']
			: false;
	}

}
?>