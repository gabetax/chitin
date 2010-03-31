<?php

include_once "interns/Intern.php";

class TemplateIntern extends Intern {
	const DESCRIPTION = "Builds a form and index template";
	public function manifest () {
		$this->template("view_index.php", "include/templates/".Inflector::pluralize($this->name)."/index.php");
		$this->template("view_form.php", "include/templates/".Inflector::pluralize($this->name)."/form.php");
	}
}

?>