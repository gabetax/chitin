<?php
/**
 * ChitinUrl
 *
 * This object is heavily influenced by the Eclipse Url object. It was written
 * as the Eclipse object did not support
 * - mod_rewrite driven URL's (using REWRITE_URL)
 * - disabling urlencode()ing of variables, which is sometimes needed for post processing
 * - get variable ignore list, for variables you know you don't want appearing in your links
 *
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @version $Id: ChitinUrl.php 68 2007-08-24 16:07:26Z gabebug $
 * @copyright Mudbug Media, 2006-06-13
 * @package Chitin
 */

/**
 * Represents a URL for the purpose of manipulating GET variables
 *
 * @package Chitin
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 */
class ChitinUrl
{

	/**
	 * URL without any GET values appended to it
	 * @var string
	 */
	var $basename;
	
	/**
	 * Associative array of un-encoded get variables, where the key is the
	 * variable name, and the value is the variable value.
	 *
	 * @var array
	 */
	var $get_vars;

	/**
	 * Associative array of variable names paired with boolean values of
	 * whether they variables should be urlencode()d or not
	 *
	 * @var array
	 */
	var $urlencode;
	
	/**
	 * Indexed array of GET variables which will not be allowed.
	 * @var array 
	 */
	var $get_var_ignore_list;

	function ChitinUrl ($query_string = '')
	{
		$this->basename = '';
		$this->get_vars = array();
		$this->urlencode = array();
		$this->get_var_ignore_list = array('dispatch_original_path');
		
		if (empty($query_string))
			$this->setFromCurrent();
		else
			$this->set($query_string);
	}
	
	
	/**
	 * Set the URL represented from the current page
	 *
	 * By default this function will pull in the absolute path of the current
	 * URL, but will not include the protocol, authentication, and port. Such
	 * full URLs should be manually specified with 'set'
	 *
	 * This function gives preference to the REDIRECT_URL, which is set by
	 * mod_rewrite and other Apache modules for transparent handling.
	 *
	 * @return void
	 */
	function setFromCurrent ()
	{
		$url = isset($_SERVER['REDIRECT_URL']) ?
			$_SERVER['REDIRECT_URL'] :
			$_SERVER['SCRIPT_NAME'];
		if (isset($_SERVER['REDIRECT_QUERY_STRING'])) $url .= '?' . $_SERVER['REDIRECT_QUERY_STRING'];
		else if (!empty($_SERVER['QUERY_STRING'])) $url .= '?' . $_SERVER['QUERY_STRING'];
		
		$this->set($url);
	}
	
	/**
	 * Set the URL represented from a manually provided string
	 *
	 * @param string $url Full URL to represent, including query string if applicable.
	 * @return void
	 */
	function set ($url)
	{
		$pieces = explode('?', $url, 2);
		$this->basename = $pieces[0];
		
		if (isset($pieces[1])) {
			$this->_parseQueryString($pieces[1]);
		}
	}
	
	/**
	 * Parse a query string into the $variables array
	 *
	 * @private
	 * @param string $str Query string; all content that comes after a ? in a url
	 * @return void
	 */
	function _parseQueryString ($str)
	{
		foreach (explode('&', $str) as $var) {
			$pieces = explode('=', $var);
			if (!isset($pieces[1])) $pieces[1] = '';
			$this->setVar(urldecode($pieces[0]), urldecode($pieces[1]));
		}
	}
	
	/**
	 * Return the formatted URL
	 *
	 * @return string
	 */
	function get () {
		if (count($this->get_vars) == 0)
			return $this->basename;

		$encoded_vars = array();
		foreach ($this->get_vars as $name => $value) {
			$encoded_vars[] = (($this->urlencode[$name]) ? urlencode($name) : $name) . '=' . (($this->urlencode[$name]) ? urlencode($value) : $value);
		}
		
		return $this->basename . '?' . join('&', $encoded_vars);
	}
	
	
	/**
	 * Set a GET variable
	 *
	 * You may optionally disable URL encoding of a particular value if you
	 * need to do post processing on the URL, such as for the PEAR Pager object
	 *
	 * @param string $name Name of the 
	 * @param string $value Value of a 
	 * @param boolean $urlencode [Optional, Default: true] URL encode the $name and $value
	 * @return void
	 */
	function setVar ($name, $value, $urlencode = true)
	{
		if (in_array($name, $this->get_var_ignore_list))
			return;
			
		if (is_null($value) || $value === false) {
			unset($this->get_vars[$name]);
			return;
		}
		
		$this->get_vars[$name] = $value;
		$this->urlencode[$name] = $urlencode;
	}
	
	/**
	 * Return the value of a previously set 
	 *
	 * @return void
	 */
	function getVar ($name) {
		return (isset($this->get_vars[$name])) ? $this->get_vars[$name] : null;
	}


} // END class ChitinUrl

?>