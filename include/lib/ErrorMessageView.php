<?php
/**
 * Displays a single error message
 *
 * @author Gabe Martin-Dempesy
 * @version $Id: ErrorMessageView.php 150 2007-10-10 20:46:42Z gabebug $
 * @copyright Mudbug Media, 2007-04-20
 * @package Chitin
 * @subpackage Views
 */

include_once 'views/BaseView.php';

/**
 * Displays a single error message
 * @package Chitin
 * @subpackage Views
 */

class ErrorMessageView extends BaseView {

	function ErrorMessageView () {
		$this->BaseView();
	}

	/**
	 * Uses the following vars:
	 * - mixed 'message' Message to display
	 */
	function display () {
		echo $this->vars['message'];
	}
}
?>