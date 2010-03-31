<?php

include_once "interns/Intern.php";

class ModelIntern extends Intern {
	const DESCRIPTION = "Builds a BaseRow";
	public function manifest () {
		$timestamp = date('YmdHis');
		$this->template("model.php", "include/models/".Inflector::camelize($this->name).".php");
		$this->template("sql-schema.php", "include/sql/{$timestamp}_".Inflector::pluralize($this->name).".sql");
	}
}

?>
