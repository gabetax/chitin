<?php

/**
 * Variables used:
 * - array 'errors' List of error messages
 */

if (isset($this->vars['errors']) && (count($this->vars['errors']) > 0)) {
// Copy and paste this code into the body of the HTML above the start of the form (and delete this line)
	echo "<div class=\"error_message\">There were errors in your submission.  Please review them and correct the form:<ul>\n";
	foreach ($this->vars['errors'] as $field => $error) {
		echo "\t<li><label for=\"$field\">$error</label></li>\n";
	}
	echo "</ul></div>";
}
?>
