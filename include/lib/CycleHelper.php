<?php
/**
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @copyright Mudbug Media, 2007-07-18
 * @version $Id: CycleHelper.php 68 2007-08-24 16:07:26Z gabebug $
 * @package Chitin
 * @subpackage Helpers
 */

/**
 * Iteratively returns progressive elements in an array
 * @package Chitin
 * @subpackage Helpers
 */
class CycleHelper {

	/**
	 * Iteratively returns progressive elements in an array
	 *
	 * @param mixed $param
	 * - array 'values' [required] List of values to cycle through
	 * - string 'name' [optional] Unique identifier for a different cycle
	 * @return string
	 */
	function cycle ($params) {
		$values = $params['values'];
		$name = (isset($params['name'])) ? $params['name'] : 'default';
		
		// key = $name, value = point to $value
		static $progress = null;
		if (is_null($progress))
			$progress = array();
		
		if (!isset($progress[$name]))
			$progress[$name] = - 1;
		
		$progress[$name] = ($progress[$name] + 1) % count($values);
		return $values[$progress[$name]];
	}
}
?>