<?php

include_once "interns/Intern.php";

class ControllerIntern extends Intern {
	const DESCRIPTION = "Builds a BaseController";
	public function manifest () {
		$this->template("controller.php", "include/controllers/".Inflector::camelize(Inflector::pluralize($this->name))."Controller.php");
	}
}

?>