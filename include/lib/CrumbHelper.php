<?php
/**
 * @version $Id$
 * @copyright Mudbug Media, 2008-11-18
 * @package Chitin
 */

/**
 * The CrumbHelper renders HTML to display bread crumbs for the current (or
 * provided) URL.
 * @package Chitin
 * @subpackage Crumb
 */
class CrumbHelper {
	static protected $config_defaults = array(
		'separator' => ' &gt; ',
		'format'    => '<a href="%s">%s</a>',
		);
	
	/**
	 * @param mixed $params
	 * - string 'title' [Optional] Title to apply to the current page (leaf crumb). Default: guessed from current URL's filename
	 * - string 'url'   [Optional] URL to generate breadcrumbs for. Default: current page's URL
	 * - string 'separator' Seperator to place between Crumbs, e.g. ' &gt; '
	 * - string 'format' sprintf for how to present each crumb except for the leaf. $1 = URL, $2 = Title
	 */
	static public function render ($params = array()) {
		if (!isset($params['url']))
			$params['url'] = LinkHelper::offset();

		// Load all of the config values into $params if they are not specified in $params.  First try the global config, then try the defaults
		foreach (self::$config_defaults as $key => $value)
			if (!isset($params[$key]))
				$params[$key] = (isset($GLOBALS['config']['crumbhelper'][$key])) ?
					$GLOBALS['config']['crumbhelper'][$key] :
					$value;

		$return = '';
		$crumbs = CrumbList::getCrumbs($params['url']);
		for ($i = 0; $i < count($crumbs) - 1; $i++) {
			$return .= sprintf($params['format'], LinkHelper::url($crumbs[$i]->url), $crumbs[$i]->title) . $params['separator'];
		}
		
		// Append the leaf node, unlinked.  If we have a 'title' manually passed, use that instead of the last crumb's title, which is probably auto-generated
		$return .= (isset($params['title'])) ?
			$params['title'] :
			$crumbs[$i]->title;
		
		return $return;
	}
}


?>