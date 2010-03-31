<?php

include_once 'vendor/phpmailer/class.phpmailer.php';

/**
 * Abstract class that implements specific email messages
 *
 * BaseMailer extends PHPMailer.  All customization to the email, such as
 * subject, CCs, headers, etc, should be assigned with PHPMailer's methods.
 *
 * Each implemented mailer must define either or both the 'textBody' and
 * 'htmlBody' methods.  These methods should write output directly to stdout
 * and should operate on $this->vars, similar to BaseView::display
 *
 * @see BaseView::display
 * @link http://phpmailer.sourceforge.net/docs/PHPMailer/PHPMailer.html
 * @package Chitin
 * @subpackage Mailers
 * @version $Id: BaseMailer.php 68 2007-08-24 16:07:26Z gabebug $
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 */
/* abstract */ class BaseMailer extends PHPMailer {

	var $vars;
	
	function BaseMailer () {
		$this->From = 'postmaster@' . $_SERVER['HTTP_HOST'];
		if (isset($GLOBALS['config']['site_name']))
			$this->FromName = $GLOBALS['config']['site_name'];
		$this->vars = array();
	}

	/**
	 * Returns the STDOUT of a provided method of this class via output buffering.
	 * @access private
	 * @param string $function_name Name of a method of $this
	 * @return string
	 */
	function _capture ($method) {
		ob_start();
		call_user_func(array($this, $method));
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	/**
	 * Capture the output from textBody and/or htmlBody and sends the message
	 */
	function Send () {
		$html = false;
		
		if (method_exists($this, 'htmlBody')) {
			$html = true;
			$this->IsHTML(true);
			$this->Body = $this->_capture('htmlBody');
		}

		if (method_exists($this, 'textBody')) {
			// If this is an HTML email, the 'text' should go into the AltBody instead.
			// (weird syntax to do pointers for assigning class variables, huh?)
			$this->{(($html) ? 'AltBody' : 'Body')} = $this->_capture('textBody');
		}
		
		return PHPMailer::Send();
	}
	
	/**
	 * Set a variable for use with the view
	 *
	 * This function is imported from BaseView
	 *
	 * @param string $key Variable name to assign
	 * @param string $value Value to assign
	 */
	function assign ($key, $value) {
		if (!is_array($this->vars))
			$this->vars = array();
		$this->vars[$key] = $value;
	}
	
	/**
	 * Assign several variable => value templating pairings at once
	 *
	 * This function is imported from BaseView
	 *
	 * @param array $array Associative array of variable names => values
	 */
	function assignArray ($array) {
		if (is_array($array)) {
			$this->vars += $array;
		}
	}
	

	/*	abstract function textBody (); */
	/* abstract function htmlBody (); */

}
?>
