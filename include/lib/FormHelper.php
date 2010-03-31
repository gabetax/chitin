<?php
/**
 * FormHelper
 *
 * @author Luke Ledet <luke@mudbugmedia.com>
 * @copyright Mudbug Media, 2007-05-31
 * @version $Id: FormHelper.php 3704 2009-03-27 20:30:42Z gabebug $
 * @package Chitin
 * @subpackage Helpers
 */

include_once 'LinkHelper.php';

/**
 * Generate form elements
 * @package Chitin
 * @subpackage Helpers
 * @link https://wiki.mudbugmedia.com/index.php/FormHelper
 */
class FormHelper {

	/**
	 * Utility function to format an element's id given its name
	 *
	 * This will convert array style inputs to have a flat name, e.g.:
	 * - name => name
	 * - person[name] => person_name
	 * - person[manager][name] => person_manager_name
	 *
	 * @access private
	 * @param string $name "name" parameter for an HTML element
	 * @return string
	 */
	function _id ($name) {
		return str_replace(array('[',']'), array('_',''), $name);
	}

	/**
	 * Utility function to get the value of an element by the passed name.
	 *
	 * Values are looked for in the following order:
	 * - $this->vars['defaults']
	 * - $GLOBALS['defaults']
	 * - $params['default_value']
	 *
	 * @access private
	 * @param mixed $params
	 * @return string
	 */
	function _value ($params) {
		if (strpos($params['name'], '[')) { 
			if (strpos($params['name'], '[')+1 == strpos($params['name'], ']')) { 
				$tmp = rtrim($params['name'], '[]');
				$value = (isset($this) && isset($this->vars['defaults'][$tmp])) ? $this->vars['defaults'][$tmp] : null;
				if (is_null($value))
					$value = isset($GLOBALS['defaults'][$tmp]) ? $GLOBALS['defaults'][$tmp] : null;
			} else {
				// If the name is in the format of user[name], convert to ['user']['name']
				$tmp = preg_replace(array('/^(.*?)\[/', '/\[(.*?)\]/'), array("[\\1][", "['\\1']"), $params['name']);
				eval('$value = isset($this) && isset($this->vars[\'defaults\']' . $tmp . ') ? $this->vars[\'defaults\']' . $tmp . ' : null;');
				if (is_null($value))
					eval('$value = isset($GLOBALS[\'defaults\']' . $tmp . ') ? $GLOBALS[\'defaults\']' . $tmp . ' : null;');
			}
		}
		else {
			$value = (isset($this) && isset($this->vars['defaults'][$params['name']])) ? $this->vars['defaults'][$params['name']] : null;
			if (is_null($value))
				$value = isset($GLOBALS['defaults'][$params['name']]) ? $GLOBALS['defaults'][$params['name']] : null;
		}

		if (is_null($value) && isset($params['default_value']))
			$value = $params['default_value'];
		
		return $value;
	}

	/**
	 * Utility function to generate a single option for a select box
	 *
	 * @access private
	 * @param mixed $value
	 * @param string $label
	 * @param mixed $selected
	 * @return string
	 */
	function _option ($value, $label, $selected = null) {
		$option = "<option value=\"{$value}\"";

		if (!is_null($selected) && (strval($value) == $selected))
			$option .= ' selected="selected"';

		$option .= ">{$label}</option>\n";

		return $option;
	}

	/**
	 * Appends 'error' to the class parameter if an error exists for this attribute
	 *
	 * @access private
	 * @param array $params
	 * @param string $id_key Key in $params which correlates to the $errors key
	 */
	function _setErrorClass (& $params, $id_key = 'id') {

		$class = array();
		if (FormHelper::_hasError($params[$id_key]))
			$class[] = 'error';
		if (isset($params['class']))
			$class[] = $params['class'];
		if (!empty($class))
			$params['class'] = implode(' ', $class);
	}

	/**
	 * Determines if the given field has an entry in the errors array
	 *
	 * This function is compatible with BaseView and a global $error array
	 *
	 * @access private
	 * @param string $id Index in the errors array
	 * @return boolean
	 */
	function _hasError ($id) {
		return ((isset($this) && isset($this->vars['errors'][$id])) || isset($GLOBALS['errors'][$id]));
	}


	/**
	 * Creates an opening <form> tag
	 *
	 * Required parameters: (none)
	 *
	 * Automatic parameters:
	 * - id: (if 'name' is set)
	 * - method: defaults to "post"
	 * - action: defaults to current URL (mod_rewrite/dispatcher safe)
	 *
	 * Special parameters: (none)
	 *
	 * @param array $params
	 * @return string
	 */
	function form_open ($params = null) {
		if (is_null($params))
			$params = array();

		if (!isset($params['action'])) {
			// The current page
			$params['action'] = LinkHelper::url();
		} else {
			$params['action'] = LinkHelper::url($params['action']);
		}
		
		if (isset($params['name']) && !isset($params['id']))
			$params['id'] = FormHelper::_id($params['name']);
		
		if (!isset($params['method']))
			$params['method'] = 'post';
		
		$form = array('<form');
		foreach ($params as $key => $param) {
			$form[] = "$key=\"$param\"";
		}

		return implode(' ', $form) . '>';
	}

	/**
	 * Generate a label
	 *
	 * Required parameters:
	 * - name: Name of the associated input
	 *
	 * Special parameters:
	 * - text: Text that appears between the open and close tags
	 *
	 * Automatic parameters:
	 * - for: defaults to the id of the "name" parameter
	 * - class: "error" is appended if an error exists for the element
	 *
	 * @param array $params
	 * @return string HTML output
	 */
	function label ($params) {
		if (!isset($params['name']) && !isset($params['for'])) return false;

		if (!isset($params['text']))
			$params['text'] = 'SET THE TEXT PROPERTY';

		if (!isset($params['for']))
			$params['for'] = FormHelper::_id($params['name']);

		FormHelper::_setErrorClass($params, 'for');

		$label = array('<label');
		foreach ($params as $key => $param) {
			if (!in_array($key, array('text', 'name')))
				$label[] = "$key=\"$param\"";
		}



		return implode(' ', $label) . ">{$params['text']}</label>";
	}

	/**#@+
	 * Wrapper for the input method to shorten the code in the view
	 *
	 * Calling this function is the same as calling input(), with an appropriate 'type' parameter set
	 *
	 * Automatic parameters:
	 * - type: automatically set to the method name
	 *
	 * @see input
	 * @param array $params
	 * @return string HTML output
	 */
	function text ($params) {
		$params['type'] = 'text';
		return FormHelper::input($params);
	}
	function password ($params) {
		$params['type'] = 'password';
		return FormHelper::input($params);
	}
	function checkbox ($params) {
		$params['type'] = 'checkbox';
		return FormHelper::input($params);
	}
	function radio ($params) {
		$params['type'] = 'radio';
		return FormHelper::input($params);
	}
	function submit ($params) {
		$params['type'] = 'submit';
		return FormHelper::input($params);
	}
	function reset ($params) {
		$params['type'] = 'reset';
		return FormHelper::input($params);
	}
	function file ($params) {
		$params['type'] = 'file';
		return FormHelper::input($params);
	}
	function hidden ($params) {
		$params['type'] = 'hidden';
		return FormHelper::input($params);
	}
	function image ($params) {
		$params['type'] = 'image';
		return FormHelper::input($params);
	}
	function button ($params) {
		$params['type'] = 'button';
		return FormHelper::input($params);
	}
	/**#@-*/

	/**
	 * Generate a form input element
	 *
	 * Required parameters:
	 * - name
	 *
	 * Special parameters:
	 * - default_value: "value" to be set for element if the value parameter isn't set and if the defaults variable does not exist for the element
	 * - unchecked_value: The value a checkbox will send to a form if it is unchecked. Default: 0
	 * - src: Value will be run through LinkHelper::url();
	 *
	 * Automatic parameters:
	 * - id
	 * - class: "error" is appended if an error exists for the element
	 * - value: Fetched from defaults variable
	 *
	 * @todo Implement default_checked special parameter
	 * @param array $params
	 * @return string HTML output
	 */
	function input ($params) {
		$special_params = array('default_value', 'unchecked_value');

		if (!isset($params['name'])) return false;

		$value = FormHelper::_value($params);

		if (!isset($params['type']))
			$params['type'] = 'text';

		if (!isset($params['id']))
			$params['id'] = FormHelper::_id($params['name']);
		
		if (isset($params['src']))
			$params['src'] = LinkHelper::url($params['src']);

		FormHelper::_setErrorClass($params);

		// Checkboxes and Radio's need to examine the passed value and the potentially user-sent value
		if (in_array($params['type'], array('checkbox', 'radio'))) {
			if (!isset($params['value'])) {
				trigger_error("FormHelper::input: 'value' parameter not provided for {$params['type']} {$params['name']}");
				return false;
			}

			if (is_array($value)) {
				if (in_array($params['value'], $value))
					$params['checked'] = 'checked';
			} else if ($params['value'] == $value) {
				$params['checked'] = 'checked';
			}
		}

		$params['value'] = (isset($params['value'])) ? htmlentities($params['value'], ENT_COMPAT, 'UTF-8') : htmlentities($value, ENT_COMPAT, 'UTF-8');

		$input = array('<input');
		foreach ($params as $key => $param) {
			if (!in_array($key, $special_params))
				$input[] = "$key=\"$param\"";
		}
		$input[] = '/>';

		// Checkboxes append a hidden input with the "unchecked" value
		if ($params['type'] == 'checkbox') {
			if (!isset($params['unchecked_value'])) $params['unchecked_value'] = '0';
			$input[0] = FormHelper::input(array('name' => $params['name'], 'type' => 'hidden', 'value' => $params['unchecked_value'], 'id' => $params['id'] . '_hidden')) . "\n" . $input[0];
		}

		return implode(' ', $input);
	}

	/**
	 * Generate a form input element
	 *
	 * Required parameters:
	 * - name
	 *
	 * Special parameters:
	 * - default_value: "value" to be set for element if the value parameter isn't set and if the defaults variable does not exist for the element
	 *
	 * Automatic parameters:
	 * - id
	 * - class: "error" is appended if an error exists for the element
	 * - value: Fetched from defaults variable
	 *
	 * @param array $params
	 * @return string HTML output
	 */
	function textarea ($params) {
		// 'value' is not really a special parameter, we just want it inside of the tags instead of a parameter
		$special_params = array('value', 'default_value');
		if (!isset($params['name'])) return false;

		if (!isset($params['id']))
			$params['id'] = FormHelper::_id($params['name']);

		FormHelper::_setErrorClass($params);

		$params['value'] = (isset($params['value'])) ? htmlentities($params['value'], ENT_COMPAT, 'UTF-8') : htmlentities(FormHelper::_value($params), ENT_COMPAT, 'UTF-8');
		$input = array('<textarea');
		foreach ($params as $key => $param) {
			if (!in_array($key, $special_params))
				$input[] = "$key=\"$param\"";
		}
		return implode(' ', $input) . '>' . $params['value'] . '</textarea>';
	}


	/**
	 * Generate a list of option elements
	 *
	 * Required parameters:
	 * - name
	 * - options: Parameters for FormHelper::options
	 *
	 * Typical parameters:
	 * - name: "name" property for the parent <select> class, used to control which option is selected by default.  If no name is supplied, no option will be selected
	 *
	 * @see options
	 * @param array $params
	 * @return string HTML output
	 */
	function select ($params) {
		$special_params = array('value', 'options');
		if (!isset($params['name'])) return false;

		if (!isset($params['id']))
			$params['id'] = FormHelper::_id($params['name']);

		FormHelper::_setErrorClass($params);

		$input = array('<select');
		foreach ($params as $key => $param) {
			if (!in_array($key, $special_params))
				$input[] = "$key=\"$param\"";
		}

		// If we want options with this select, append the name of the select
		// to the options array before sending it to the options helper
		if (isset($params['options'])) {
			if (!isset($params['options']['items']))
				$params['options']['items'] = $params['options'];
			$params['options']['name'] = $params['name'];

			$input[] = ">" . FormHelper::options($params['options']) . "</select>";
		}
		else
			$input[] = "></select>";

		return implode(' ', $input);
	}

	/**
	 * Generate a list of option elements
	 *
	 * Required parameters:
	 * - 'items' mixed List of items to be converted to <option> tags.  Can take the forms of:
	 * - # associative array of strings where the key is the "HTML value" and the value is the "HTML label"
	 * - # array of associative arrays, which requires the 'value' and 'label' parameters
	 * - # array of BaseRows, which requires the 'value' and 'label' parameters
	 *
	 * Note that if 'items' is not present in the params, items takes on the
	 * value of the entire params array. e.g. The following are equivalent:
	 * # FormHelper::options(array('a','b', 'c'))
	 * # FormHelper::options(array('items' => array('a','b', 'c')))
	 *
	 * Typical parameters:
	 * - 'header' Prefix the options with an empty-valued option
	 * - 'footer' Prefix the options with an empty-valued option
	 * - 'name' Used to determine the default selected value
	 *
	 * @param array $params
	 * @return string HTML output
	 */
	function options ($params) {
		$options = '';
		$selected = (isset($params['name'])) ? FormHelper::_value($params) : null;

		if (isset($params['default_value']) && $selected == null && empty($params['selected'])) {
			$selected = $params['default_value'];
		}
		
		// Append a header option
		if (isset($params['header'])) {
			if (is_array($params['header']))
				$options .= FormHelper::_option($params['header']['value'], $params['header']['label'], $selected);
			else
				$options .= FormHelper::_option('', $params['header'], $selected);
		}

		// If the params is in the form array[value] = label...
		if (!isset($params['items'])) {
			foreach ($params as $value => $label) {
				$options .= FormHelper::_option($value, $label, $selected);
			}
		}
		// if params has a value and label set, use them
		elseif (isset($params['value'], $params['label'])) {
			foreach ($params['items'] as $item) {
				if (is_array($item))
					$options .= FormHelper::_option($item[$params['value']], $item[$params['label']], $selected);
				else if (is_object($item))
					$options .= FormHelper::_option($item->{$params['value']}, $item->{$params['label']}, $selected);
			}
		}
		else {
			foreach ($params['items'] as $value => $label) {
				$options .= FormHelper::_option($value, $label, $selected);
			}
		}

		// Append a footer option
		if (isset($params['footer'])) {
			if (is_array($params['footer']))
				$options .= FormHelper::_option($params['footer']['value'], $params['footer']['label'], $selected);
			else
				$options .= FormHelper::_option('', $params['footer'], $selected);
		}

		return $options;
	}

}
?>
