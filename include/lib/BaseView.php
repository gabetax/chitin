<?php

/**
 * Parent View class
 *
 * @package Chitin
 * @subpackage Views
 * @version $Id: BaseView.php 1788 2008-07-07 20:24:27Z gabebug $
 * @author Gabe Martin-Dempesy <gabe@mudbuginfo.com>
 */
class BaseView {

	public $vars;
	/** @var boolean */
	protected $wrapper;
	
	public function __construct () {
		$this->wrapper = true;
		$this->vars = array();
	}
	
	/**
	 * Set a variable for use with the view
	 * @param string $key Variable name to assign
	 * @param string $value Value to assign
	 */
	public function assign ($key, $value) {
		if (!is_array($this->vars))
			$this->vars = array();
		$this->vars[$key] = $value;
	}
	
	/**
	 * Assign several variable => value templating pairings at once
	 * @param array $array Associative array of variable names => values
	 */
	public function assignArray ($array) {
		if (is_array($array)) {
			$this->vars += $array;
		}
	}
	
	/**
	 * Display output of the view
	 * 
	 */
	public function display () {
	}
	
	/** 
	 * Return the output of display() as a string
	 * @return string
	 */
	public function getOutput () {
		ob_start();
		
		$class = get_class($this);
		
		// PHP5 reverse compatibility -- PHP4's get_class() returned all lower case
		if (isset($GLOBALS['config']['BaseViewPHP4Compat']) && version_compare('5.0.0', phpversion(), '<='))
			$class .= ' ' . strtolower(get_class($this));
		
		if ($this->wrapper) echo '<div class="'. $class .'">';
		$this->display();
		if ($this->wrapper) echo '</div>';
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
}
?>
