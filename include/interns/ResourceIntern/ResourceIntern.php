<?php

include_once "interns/Intern.php";

class ResourceIntern extends Intern {
	const DESCRIPTION = "Builds Chitin MVC for an object for this Chitin installation";
	public function manifest () {
		$timestamp = date('YmdHis');
		$this->template("model.php", "include/models/".Inflector::camelize($this->name).".php");
		$this->template("controller.php", "include/controllers/".Inflector::camelize(Inflector::pluralize($this->name))."Controller.php");
		$this->template("view_index.php", "include/templates/".Inflector::pluralize($this->name)."/index.php");
		$this->template("view_form.php", "include/templates/".Inflector::pluralize($this->name)."/form.php");
		$this->template("sql-schema.php", "include/sql/{$timestamp}_".Inflector::pluralize($this->name).".sql");
	}
}

?>
