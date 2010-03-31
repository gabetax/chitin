<?php
/**
 * MD5 one-way hashing ChitinCrypt object
 *
 * As MD5 is a hash, there is no way to decrypt anything that you encrypt.
 *
 * @author Gabe Martin-Dempesy
 * @version $Id$
 * @copyright Mudbug Media, 2007-07-09
 * @package Chitin
 * @subpackage Crypt
 */
include_once 'ChitinCrypt.php';

class MD5Crypt extends ChitinCrypt {

	function MD5Crypt ($options = null) {
		$this->ChitinCrypt($options);
	}

	/**
	 * Hash provided plain text into cipher output
	 * @param string $plaintext
	 * @return string encrypted text
	 */
	function encrypt ($plaintext) {
		return md5($plaintext);
	}

	/**
	 * Since MD5 is a Hash (one way) algorithm, we can not reverse it
	 * @returns false
	 */
	function decrypt ($cipher) {
		return false;
	}
}

?>
