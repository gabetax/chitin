<?php
/**
 * AES (Rijndael) two-way ChitinCrypt object
 *
 * @author Gabe Martin-Dempesy
 * @version $Id$
 * @copyright Mudbug Media, 2007-07-09
 * @package Chitin
 * @subpackage Crypt
 */

include_once 'ChitinCrypt.php';

class AESCrypt extends ChitinCrypt {
	var $key;

	var $alt;
	var $mode;
	var $iv;

	function AESCrypt ($options = null) {
		$this->ChitinCrypt($options);
		$this->alg = MCRYPT_RIJNDAEL_256;
		$this->mode = MCRYPT_MODE_ECB;
		$this->iv = mcrypt_create_iv(mcrypt_get_iv_size($this->alg, $this->mode), MCRYPT_RAND);
		$this->setKey(',3/"OL>2Z,(5PJ\J/0K9ZJ*\@OG`O--X');
	}

	/**
	 * Encrypt provided plain text into cipher output
	 * @param string $plaintext
	 * @return string encrypted text
	 */
	function encrypt ($plaintext) {
		if (empty($plaintext))
			return '';
			
		return base64_encode(mcrypt_encrypt($this->alg, $this->key, $plaintext, $this->mode, $this->iv));
	}

	/**
	 * Decrypt provided cipher into plain text output
	 * @param string $cipher
	 * @return string plaintext text
	 */
	function decrypt ($cipher) {
		if (empty($cipher))
			return '';
			
		return trim(mcrypt_decrypt($this->alg, $this->key, base64_decode($cipher), $this->mode, $this->iv));
	}

	/**
	 * This will allow you to set a new key to use different from the
	 * default defined in the consturctor.  If you use an alternate key,
	 * you must set it before all encrypt() and decrypt() uses
	 *
	 * Good keys can be generated via: "head /dev/urandom | uuencode -"
	 *
	 * @param string $newkey New decryption key to be set
	 * @return void
	 */
	function setKey ($key) {
		$maxlength = mcrypt_get_key_size($this->alg, $this->mode);
		if (strlen($key) > $maxlength) {
			trigger_error("AESCrypt::setKey(): warning, maxmimum key length ($maxlength) exceeded, truncating your key.", E_USER_NOTICE);
			$key = substr($key, 0, $maxlength);
		}

		$this->key = $key;
	}
}

?>
