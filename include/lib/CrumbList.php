<?php
/**
 * @version $Id$
 * @copyright Mudbug Media, 2008-11-18
 * @package Chitin
 */

/**
 * @package Chitin
 * @subpackage Crumb
 */
class CrumbList {
	/**
	 * @var array Hash of crumb instances.  The key is equal to the Crumb->url property for easy lookups.
	 */
	static protected $crumbs = array();
	
	/**
	 * @static
	 * @param mixed A Crumb or an array of Crumb objects to register
	 */
	static public function add ($crumb) {
		if (is_array($crumb)) {
			foreach($crumb as $c)
				self::add($c);
			return;
		}
		
		if (!($crumb instanceof Crumb))
			throw new CrumbException("CrumbList::add: provided variable is not a Crumb object: " . var_export($crumb, true));
			
		self::$crumbs[$crumb->url] = $crumb;
	}
	
	/**
	 * Empty out any previous Crumbs set by ::add()
	 * @static
	 */
	static public function clear () {
		self::$crumbs = array();
	}
	
	/**
	 * Fetch a flat array of Crumb nodes for a url from the root to the leaf
	 * @static
	 * @param string $url URL to fetch crumbs for, e.g. "people/roy.php"
	 * @return array Flat array of Crumb instances, with [0] being the root, and the tail being the leaf
	 */
	static public function getCrumbs ($url) {
		$crumbs = array(self::getCrumb($url));
		while (!is_null($crumbs[0]->parent)) {
			array_unshift($crumbs, self::getCrumb($crumbs[0]->parent));
			if (count($crumbs) > 100) {
				throw new CrumbException("CrumbList::getCrumbs() has traversed over 100 nodes. Potential infite loop defined via 'parent' in a node.");
			}
		}
		
		// Filter out crumbs with 'null' for the title
		$return = array();
		for ($i = 0; $i < count($crumbs); $i++)
			if (!is_null($crumbs[$i]->title))
				$return[] = $crumbs[$i];
		
		return $return;
	}
	
	/**
	 * Return a Crumb instance for a URL that has been previously added to the
	 * list.  If no crumb has been previously added for the URL, one is created
	 * @static
	 * @param string $url URL to fetch crumbs for, e.g. "people/roy.php"
	 * @return Crumb
	 */
	static public function getCrumb ($url) {
		// If we don't have one currently set, auto-generate one and add it.
		if (!isset(self::$crumbs[$url]))
			self::add(new Crumb(array('url' => $url)));
		
		return self::$crumbs[Crumb::cleanURL($url)]; // when instantiating Crumbs, they clean the URL to trim trailing 'index.*'
	}

}


?>