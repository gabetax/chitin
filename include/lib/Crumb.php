<?php
/**
 * @version $Id$
 * @copyright Mudbug Media, 2008-11-18
 * @package Chitin
 */


class CrumbException extends Exception {}

/**
 * The Crumb is used by the CrumbHelper to display bread crumbs on pages
 *
 * Each instance of a Crumb object models one URL, and stores the page's
 * title, and the URL of its parent page.
 */
class Crumb {
	public $parent;
	public $url;
	public $title;
	
	/**
	 * @param mixed $params Values to store in the crumb, including:
	 * - string 'url' default: current URL relative to the site root, example: "people/scott.php"
	 * - string 'parent' default: parent directory of current URL, example: "people/"
	 * - string 'title' default: humanized from page name, example: "Roy"
	 */
	public function __construct ($params = array()) {
		$this->url =  self::cleanUrl((isset($params['url'])) ? $params['url'] : LinkHelper::offset());
		
		// Trim "index*" from URLs that contain it
		
		
		$this->parent = (array_key_exists('parent', $params)) ?
			$params['parent'] :
			(($this->url == '/' || $this->url == '') ? null : dirname($this->url) . '/');
		
		/* Special Cases!
		 * dirname('/a/') => '/'
		 * dirname('a/')  => '.'
		 */
		if ($this->parent == '//') $this->parent = '/';
		if ($this->parent == './') $this->parent = '';
			
		$this->title = (array_key_exists('title', $params)) ?
			$params['title'] :
			ucwords(Inflector::humanize(str_replace(array('.','-'), array(' ',' '), pathinfo($this->url, PATHINFO_FILENAME))));
		if (!isset($params['title']) && ($this->url == '/' || $this->url == ''))
			$this->title = "Home";
	}
	
	/**
	 * Cleanup a provided URL by triming off "index" or "index.*" from the end of URLs
	 * @param string $url
	 * @param string Cleaned up URL
	 */
	static public function cleanUrl ($url) {
		if (substr($url, -1) != '/' && strpos(basename($url), 'index') === 0) 
			$url = dirname($url) . '/';
		return $url;
	}
}


?>