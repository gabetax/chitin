<?php
/**
 * @author Gabe Martin-Dempesy
 * @version $Id: TemplateView.php 1945 2008-07-31 19:37:56Z gabebug $
 * @copyright Mudbug Media, 2007-04-20
 * @package Chitin
 * @subpackage Views
 */

class TemplateViewException extends Exception {}
class TemplateViewFileNotFoundException extends TemplateViewException {}

/**
 * BaseView object which loads content from flat files inside of the "templates" dirctory.
 *
 * @package Chitin
 * @subpackage Views
 */
class TemplateView extends BaseView {

	/**
	 * @var string Path to the directory containing template files
	 */
	private $template_path;

	/**
	 * @var string Name of the template file, relative to the templates directory
	 */
	private $filename;

	/**
	 * @param string $filename Name of the template file, relative to the templates directory
	 */
	public function __construct ($filename) {
		parent::__construct();
		$this->template_path = 'templates';
		$this->filename = $filename;
		$this->wrapper = false;
		
		if (!ChitinFunctions::file_exists_in_include_path($this->template_path . '/' . $this->filename))
			throw new TemplateViewFileNotFoundException("Unable to locate template \"{$this->template_path}/{$this->filename}\"");
	}

	public function display () {
		extract($this->vars);
		include $this->template_path . '/' . $this->filename;
	}

	/**
	 * Render a partial template within the current template
	 * @param string $target Either a template filename to render, or an instance of a Baserow
	 * @param mixed $options Named parameters
	 * - 'locals' Hash of variables to pass the partial.  If not specified, all of the current template's variables will be passed
	 */
	protected function render ($target, $options = array()) {
		if (!isset($options['locals'])) $options = array('locals' => $this->vars);

		if ($target instanceof BaseRow) {
			$class = Inflector::underscore(get_class($target));
			$path = Inflector::pluralize($class) . '/_' . $class . '.php';
			$options['locals'] = array($class => $target);
		} else {
			$path = $target;
		}

		$view = new TemplateView($path);
		$view->assignArray($options['locals']);
		$view->display();
	}
}

?>