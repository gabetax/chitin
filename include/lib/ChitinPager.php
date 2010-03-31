<?php
/**
 * ChitinPager
 *
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @version $Id: ChitinPager.php 2319 2008-10-13 21:20:37Z gabebug $
 * @copyright Mudbug Media, 2006-06-13
 * @package Chitin
 */


/**
 * Displays page numbers for navigating large lists of information in steps
 *
 * @package Chitin
 * @subpackage Pager
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 */
class ChitinPager
{
	/**
	 * @var array Associative array of configuration variables
	 */
	protected $config;

	public function __construct ($config = null) {
		// Set the default configuration values
		$this->config = array(
			'max_pages' => 10,
			'border_pages' => 2,
			'separator' => '',
			'border_separator' => '....',
			'url_type' => 'get',
			'previous_page_text' => '&#171; Previous',
			'previous_page_title' => 'View previous page',
			'next_page_text' => 'Next &#187;',
			'next_page_title' => 'View next page',
			'page' => 1,
			'per_page' => 15,
			'sort_field' => null,
			'sort_order' => null,
			'link_to_current_page' => false,
			'class_wrapper' => 'chitin-pager',
			'class_active' => 'current',
			'view_class' => 'PagerView',
			'trailing_pages' => 1,
		);
		
		if (is_array($config)) {
			foreach ($config as $key => $value)
				$this->setConfig($key, $value);
		}
	}
	
	/**
	 * Set one or many configuration options
	 * @param string $key specific key to set
	 * @param string $value value to set
	 */
	public function setConfig ($key, $value) {
		if (isset($this->config[$key]))
			$this->config[$key] = $value;
		else
			user_error("ChitinPager::setConfig(): Invalid configuration key: $key", E_USER_NOTICE);
	}
	
	/**
	 * Return all or one configuration variable
	 * @param string $key If specified, returns one paritcular configuration variable
	 * @return mixed
	 */
	public function getConfig ($key = null) {
		if (is_null($key)) {
			return $this->config;
		} else {
			return (isset($this->config[$key])) ?
				$this->config[$key] :
				null;
		}
	}
	
	/**
	 * Sets the PagerUrl object that will be used to determine and set page number, per page, and sort options.
	 *
	 * @param string $type
	 * @return PagerUrl
	 */
	public function setPagerUrlType ($type) {
		$this->url = $this->_PagerUrlFactory($type);
		$this->setConfig('url_type', $type);
		
		return $this->url;
	}
	
	public function getPagerUrl () {
		if (!isset($this->url))
			$this->_PagerUrlFactory($this->config['url_type']);
		return $this->url;
	}
	
	/**
	 * Instantiate a PagerUrl type
	 *
	 * Constructed type will be $typePagerUrl
	 *
	 * @access private
	 * @param string $type
	 * @return PagerUrl
	 */
	protected function _PagerUrlFactory ($type) {
		$class_name = $type . "PagerUrl";
		if (!class_exists($class_name)) {
			user_error("Invalid class name: $class_name.  Please supply a valid type to Pager::setUrlType()", E_USER_ERROR);
			return;
		}
		
		eval('$object = new ' . $class_name . '($this->config);');
		$this->url = $object;
		return $object;
	}

	/**
	 * Fetch HTML output generated from the PagerView
	 *
	 * @param integer $total_items Total items which we are paging
	 * @return string
	 */
	public function getOutput ($total_items) {
		eval('$view = new '.$this->config['view_class'].'();');

		$url = $this->getPagerUrl();

		$view->assignArray($this->getConfig());
		$view->assign('url', $url);
		$view->assign('total_pages', ceil($total_items / $url->get('per_page')));
		$view->assign('total_items', $total_items);

		return $view->getOutput();
	}
	
	/**
	 * Return a BaseRow scope array to chain with a find() query
	 *
	 * The scope will set the 'page', 'per_page', 'sort_fields', and 'sort_directions' find parameters.
	 *
	 * @return array
	 */
	public function getScope () {
		$url = $this->getPagerUrl();
		$scope = array(
			'page' => $url->get('page'), 
			'per_page' => $url->get('per_page'),
			'sort_fields' => $url->get('sort_field'), 
			'sort_directions' => $url->get('sort_order'), 
		);
		
		return $scope;
	}

} // END class ChitinUrl



/**
 * Interface used to determine page number, per page, and sort order from and for a URL
 *
 * @package Chitin
 * @subpackage Pager
 */
abstract class PagerUrl {
	
	/**
	 * @var array Associative array of configuration variables from ChitinPager
	 */
	var $config;

	/**
	 * @var array Associative array of values parsed from the Url
	 */
	var $values;
	
	function __construct ($config) {
		$this->config = $config;
		$this->values = array();
	}
	
	/**
	 * Returns the Url to use for a given pager number
	 * @param integer $page_number
	 * @return string Url to use for current page
	 */
	function getUrlForPage ($page_number) {
	}
	
	/**
	 * Fetch one of the following values about the URL
	 *
	 * @param string $field One of the following values:
	 * - 'per_page'
	 * - 'page'
	 * - 'sort_order'
	 * - 'sort_field'
	 * @return string
	 */
	function get ($field) {
		return $this->values[$field];
	}
}


/**
 * PagerUrl object that maintains all of its variables via GET variables
 *
 * Management of these variables is performed via ChitinUrl object
 *
 * @package Chitin
 * @subpackage Pager
 */
class getPagerUrl extends PagerUrl {

	function __construct ($config) {
		parent::__construct($config);
		$this->url = new ChitinUrl();
		// Wipe out the GET variable set by the dispatcher
		$this->url->setVar('dispatch_original_path', null);
		$fields = array('per_page', 'sort_field', 'sort_order', 'page');
		foreach ($fields as $field) {
			$this->values[$field] = $this->url->getVar($field);
			if (is_null($this->values[$field])) $this->values[$field] = $this->config[$field];
		}
	}
	
	/**
	 * Returns the Url to use for a given pager number
	 * @param integer $page_number
	 * @return string Url to use for current page
	 */
	function getUrlForPage ($page_number) {
		$temp_url = $this->url;
		$temp_url->setVar('page', $page_number);
		return $temp_url->get();
	}
}

?>
