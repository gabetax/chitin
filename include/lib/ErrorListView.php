<?php
/**
 * Displays a list of error messages
 *
 * @author Gabe Martin-Dempesy
 * @version $Id: ErrorListView.php 150 2007-10-10 20:46:42Z gabebug $
 * @copyright Mudbug Media, 2007-04-20
 * @package Chitin
 */

include_once 'views/BaseView.php';

/**
 * Displays a list of error messages
 * @package Chitin
 * @subpackage Views
 */

class ErrorListView extends BaseView {

	function ErrorListView () {
		$this->BaseView();
	}

	/**
	 * Uses the following vars:
	 * - mixed 'errors' List of errors to display
	 */
	function display () {
		if (isset($this->vars['errors']) && (count($this->vars['errors']) > 0)) {
		// Copy and paste this code into the body of the HTML above the start of the form (and delete this line)
			echo "<h2>There were errors in your submission.  Please review them and correct the form:</h2><ul>\n";
			foreach ($this->vars['errors'] as $field => $error) {
				echo "<li><label for=\"$field\">$error</label></li>";
			}
			echo "</ul>";
		}
	}
}
?>