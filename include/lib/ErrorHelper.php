<?php
/**
 * Error Helper
 *
 * @version $Id$
 * @copyright Mudbug Media, 2008-07-07
 * @package Chitin
 */

class ErrorHelper {
	/**
	 * Returns rendered HTML for a list of error messages
	 * @param mixed $errors 
	 * @return string HTML output
	 */
	static public function index ($errors) {
		$template = new TemplateView('error/list.php');
		$template->assign('errors', $errors);
		return $template->getOutput();
	}
	
	/**
	 * Renders rendered HTML for a single error message
	 * @param string $message
	 * @return string HTML output
	 */
	static public function message ($message) {
		$template = new TemplateView('error/message.php');
		$template->assign('message', $message);
		return $template->getOutput();
	}
}

?>