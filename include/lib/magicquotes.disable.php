<?php
/**
 * Manually ensure that _REQUEST is free of magic_quotes.
 *
 * If magic_quotes_gpc is enabled, this will recursively step through _REQUEST
 * and stripslashes() what was added by magic_quotes.
 *
 * This should be used with Chitin's BaseRow and PEAR DB's prepare()
 * syntax, as their parameters should not have quotes added to them.
 *
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @version 1.0
 * @copyright Mudbug Media, 2006-05-17
 * @package default
 */

if (get_magic_quotes_gpc()) {

if (!function_exists('stripslashes_arraysafe')) {
	/**
	 * A recursive implementation of stripslashes.
	 *
	 * This acts identically to its respective function, with the exception that
	 * it can be passed an array as well as a string. Arrays are passed by value
	 * so there is no risk of modification of the original array.
	 *
	 * @param mixed $val Data to strip slashes from
	 * @return mixed
	 */
	function stripslashes_arraysafe($val) {
		if (!is_array($val))
		{
			return stripslashes(trim($val));
		} else {
			foreach ($val as $k => $v) {
				$val[$k] = stripslashes_arraysafe($v);
			}
			return $val;
		}
	}
}


$_REQUEST = stripslashes_arraysafe($_REQUEST);
}

?>
