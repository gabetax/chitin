<?php
/**
 * Strategy pattern for available encryption methods
 *
 * @author Gabe Martin-Dempesy
 * @version $Id$
 * @copyright Mudbug Media, 2007-07-09
 * @package Chitin
 * @subpackage Crypt
 */

/* abstract */ class ChitinCrypt {

	function ChitinCrypt ($options = null) {
	}

	/**
	 * Return a new Crypt object depending on provided encryption algorithm
	 * @param string $type
	 * @return ChitinCrypt
	 */
	function factory ($type, $options = null) {
		$valid_types = array('PlainText', 'AES', 'MD5');
		if (!in_array($type, $valid_types)) {
			trigger_error("ChitinCrypt::factory: Invalid crypt object type: $type", E_USER_WARNING);
			return false;
		}
		
		include_once dirname(__FILE__) . "/{$type}Crypt.php";
		eval('$c = new '.$type.'Crypt($options);');
		return $c;
	}

	/**
	 * Determine if a cipher is the same content as plaintext
	 *
	 * Depending on your cipher, you may need to re-implement this in child
	 * objects to work around obstructions like salts.
	 *
	 * @param string $cipher
	 * @param string $plaintext
	 * @return boolean
	 */
	function match ($cipher, $plaintext) {
		return ($cipher == $this->encrypt($plaintext));
	}

	/**
	 * Encrypt provided plain text into cipher output
	 * @param string $plaintext
	 * @return string encrypted text
	 */
	function encrypt ($plaintext) {
		return '';
	}

	/**
	 * Decrypt provided cipher into plain text output
	 * @param string $cipher
	 * @return string plaintext text
	 */
	function decrypt ($cipher) {
		return '';
	}
}

?>