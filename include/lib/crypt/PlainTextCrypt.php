<?php
/**
 * PlainText ChitinCrypt object
 *
 * This object does not perform any encryption.  It should be used in your code
 * as a place holder to allow for later substitution with an alternate
 * ChitinCrypt object.
 *
 * @author Gabe Martin-Dempesy
 * @version $Id$
 * @copyright Mudbug Media, 2007-07-09
 * @package Chitin
 * @subpackage Crypt
 */

include_once 'ChitinCrypt.php';

class PlainTextCrypt extends ChitinCrypt {

	function PlainTextCrypt ($options = null) {
		$this->ChitinCrypt($options);
	}

	/**
	 * Return exactly what is provided
	 * @param string $plaintext
	 * @return string encrypted text
	 */
	function encrypt ($plaintext) {
		return $plaintext;
	}

	/**
	 * Return exactly what is provided
	 * @param string $cipher
	 * @return string plaintext text
	 */
	function decrypt ($cipher) {
		return $cipher;
	}
}

?>
