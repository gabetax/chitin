<?php
/**
 * MySQL to Excel Spreadsheet writer
 *
 * Changelog
 * - 2006-11-08: Initial version
 * 
 * @author Gabe Martin-Dempesy
 * @version 1
 * @copyright Mudbug Media, 2006-11-08
 * @package script
 **/

require_once 'Spreadsheet/Excel/Writer.php';
include_once 'Inflector.php';
include_once 'BaseRow.php';

class ExcelExporterException extends Exception { }
class FileNotWritableExcelExporterException extends Exception { }
/**
 * Provides us an interface to run raw queries through, without directly mucking in DB.  We need to extend this class to get around the 'abstract' requirement
 */
class ExcelExporterBaseRow extends BaseRow { }

/**
 * Exports the entire contents of a MySQL table to an Excel spreadsheet
 *
 * Usage:
 * MySQLToExcelExporter::download(array());
 *
 * Note that this class does not need to utilize ForceDownloader to set the
 * download headers, as the Spreadsheet_Excel_Writer object handles this responsibility.
 *
 * @uses Inflector
 * @uses BaseRow
 * @package Chitin
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 */
class ExcelExporter {

	/**
	 * Download an Excel spreadsheet via the browser
	 *
	 * This method will automatically set all appropriate HTTP headers, and will
	 * exit() execution of the after generation
	 *
	 * @param $params see _condenseParams() documentation
	 */
	static public function download ($params) {
		$params['mode'] = 'download';
		self::_export($params);
	}

	/**
	 * Save an Excel spreadsheet on the disk as a file
	 * @param $params see _condenseParams() documentation
	 * @throws FileNotWritableExcelExporterException
	 */
	static public function save ($params) {
		$params['mode'] = 'save';
		self::_export($params);
	}

	static protected function _export ($params) {
		$params = self::_condenseParams($params);

		switch ($params['mode']) {
			case 'download':
				$workbook = new Spreadsheet_Excel_Writer();
				$workbook->send($params['filename']); // Sends to the browser
				break;
			case 'save':
				$full_path = $params['path'] . '/' . $params['filename'];
				$workbook = new Spreadsheet_Excel_Writer($full_path);
				break;
			default:
				throw new ExcelExporterException("\$params['mode'] is an unknown type: {$params['mode']}");
		}
		
		$worksheet = $workbook->addWorksheet('Database Export');
		$params['addedFormats'] = self::_addFormats($params, $workbook);
		
		$records = self::_getRecords($params);
		$fields  = self::_getFieldNames($records);
		
		self::_setWidths($params, $worksheet, $fields);
		
		if ($params['include_field_names'])
			self::_writeFieldNames($params, $worksheet, $fields);
		
		$offset = $params['include_field_names'] ? 1 : 0;
		for ($i = 0; $i < count($records); $i++)
			self::_writeRow($params, $worksheet, $records[$i], $i + $offset);

		$workbook->close();
		
		// When downloading, exit
		if ($params['mode'] == 'download') {
			if (class_exists('ChitinLogger'))
				ChitinLogger::flush();
			exit(0); // We're sending download headers to the browser. Exit immediately.
		}
		
		if ($params['mode'] == 'save' && !file_exists($full_path)) {
			throw new FileNotWritableExcelExporterException("\$params['path'] was not a writable location: {$params['path']}");
		}
	}

	/**
	 * Sanitize and Condense down the user-provided parameters and provide default values
	 *
	 * @param array Parameters (all are optional, but either 'source' or 'query' must be provided):
	 * - 'mode' string 'download' or 'save'
	 * - 'filename' string of the filename to be generated.
	 * - 'path' string The path to save files into when using ::save()
	 * - 'source' BaseRow | string: BaseRow, BaseRow scope, or BaseRow class name to use as a data source.
	 * - 'query' string Raw SQL query to run, e.g. "SELECT * FROM people WHERE state LIKE ?"
	 * - 'params' array prepare() parameters to use with 'query', e.g. array('%LA%');
	 * - 'include_field_names' [optional] boolean Include the top row of field names? default: true
	 * - 'formats' array Associative array of formatting rules. By default 'data' and 'header' are defined.  e.g. array('odd' => array(), 'even' => array('BgColor' => 'magenta')); @see http://pear.php.net/manual/en/package.fileformats.spreadsheet-excel-writer.spreadsheet-excel-writer-workbook.addformat.php
	 * - 'formatCallback' function A lambda function (made by create_function()) returns a key from 'formats', e.g. create_function('$row, $column, $data', 'return ($row % 2) ? "even" : "odd";')
	 * - 'widths' array Associative array of column widths in units of letters, e.g array('name' => 10, 'address' => 25);
	 * @return array
	 */
	static protected function _condenseParams ($params) {
		
		// *** 'source'
		if (isset($params['source'])) {
			// Attempt to convert strings to a class instance
			if (is_string($params['source'])) {
				if (!class_exists($params['source']))
					throw new ExcelExporterException("\$params['source'] class name {$params['source']} is not defined");
				$params['source'] = new $params['source'];
			}
			
			// Verify that the object is a BaseRow instance
			if (!($params['source'] instanceof BaseRow))  
				throw new ExcelExporterException("\$params['source'] is not a BaseRow instance");
		}
		
		// *** 'query', 'params'
		if (isset($params['query']) && (!isset($params['params'])))
			$params['params'] = array();
		
		
		// *** 'filename'
		if (!isset($params['filename'])) {
			if (isset($params['source']))
				$params['filename'] = Inflector::underscore(get_class($params['source'])) . '.xls';
			else
				$params['filename'] = date('Y-m-d') . '_export.xls';
		}
		
		// *** 'path'
		if (!isset($params['path']))
			$params['path'] = getcwd();
		
		// *** 'include_field_names'
		if (!isset($params['include_field_names']))
			$params['include_field_names'] = true;
		
		// *** 'formats'
		if (!isset($params['formats']))
			$params['formats'] = array();
		if (!isset($params['formats']['header']))
			$params['formats']['header'] = array('Bold' => 700, 'Align' => 'center');
		if (!isset($params['formats']['data']))
			$params['formats']['data'] = array();

		// *** 'formatCallback'
		if (!isset($params['formatCallback']))
			$params['formatCallback'] = create_function('$row, $col, $data',
				($params['include_field_names']) ? 
				'return ($row == 0) ? "header" : "data";' :
				'return "data";'
			);
		
		if (!isset($params['widths']))
			$param['widths'] = array();
		
		return $params;
	}
	
	
	/**
	 * Adds all the formatting rules to the Worksheet
	 * @return array Associative array of added formats
	 */
	static protected function _addFormats ($params, $workbook) {
		$compiled = array();
		foreach ($params['formats'] as $name => $rules)
			$compiled[$name] = $workbook->addFormat($rules);
		return $compiled;
	}
	
	/**
	 * Set widths of columns based on $params['widths'], or based on the length of the column name by default
	 * @param array $params
	 * @param Worksheet $worksheet
	 * @param array Array of field names, from _getFieldNames()
	 */
	static protected function _setWidths ($params, $worksheet, $fields) {
		for ($i = 0; $i < count($fields); $i++) {
			$worksheet->setColumn($i, $i,
				(isset($params['widths'][$fields[$i]])) ?
					$worksheet->setColumn($i, $i, $params['widths'][$fields[$i]]) :
					strlen($fields[$i]) * 3
				);
		}
	}
	
	/**
	 * Fetch records from the database
	 *
	 * This method may fetch data two ways, depending on the $params
	 * - From BaseRow records, if 'source' is set
	 * - A raw SQL query, if 'query' is set
	 *
	 * @return array Array of records, either BaseRows or associative arrays
	 */
	static protected function _getRecords ($params) {
		if (isset($params['source']))
			return $params['source']->find();
		else if (isset($params['query'])) {
			// We could run this directly through the DB, but BaseRow already has all the exceptions setup
			$table = new ExcelExporterBaseRow();
			return $table->query($params['query'], $params['params']);
		} else
			throw new ExcelExporterException("No valid record source was provided, either 'source' or 'query'");
	}
	
	/**
	 * Return a flat array of field names
	 * @param array Results of _getRecords()
	 * @return array e.g. array('id', 'name', 'address', 'city', ...)
	 */
	static protected function _getFieldNames ($records) {
		// If we don't have records, we have no way of determining field names
		if (!isset($records[0]))
			return array();
		if ($records[0] instanceof BaseRow)
			return array_keys($records[0]->toArray());
		else if (is_array($records[0]))
			return array_keys($records[0]);
		else
			throw new ExcelExporterException("First element from _getRecords was an unexpected type: " . var_export($records[0], true));
	}
	
	/**
	 * Write field names in the top row of the worksheet, and lock it into place
	 * @param Worksheet $worksheet
	 * @param array $fields An array of string names for the field headings.
	 */
	static protected function _writeFieldNames ($params, $worksheet, $fields) {
		for ($i = 0; $i < count($fields); $i++) {
			$worksheet->write(0, $i, Inflector::humanize($fields[$i]), $params['addedFormats']['header']);
		}
		
		// Freeze the top row of the sheet
		$worksheet->freezePanes(array(1, 0, 1, 0));
	}

	/**
	 * Write one row of data
	 * @params array $params
	 * @params Worksheet $worksheet
	 * @params BaseRow|array $row
	 * @params integer $row_num
	 */
	static protected function _writeRow ($params, $worksheet, $row, $row_num) {
		if ($row instanceof BaseRow)
			$row = $row->toArray();
		
		$col_num = 0;
		foreach ($row as $value) {
			$worksheet->write($row_num, $col_num, $value, self::_getAddedFormatFromCallback($params, $row_num, $col_num, $value));
			$col_num++;
		}
	}
	
	/**
	 * Call the formatCallback and verify that the returned value exists
	 * @param array $params
	 * @param integer $col
	 * @param integer $row
	 * @param string $data
	 * @return format
	 */
	static protected function _getAddedFormatFromCallback($params, $row_num, $col_num, $data) {
		$format_name = $params['formatCallback']($row_num, $col_num, $data);
		if (!isset($params['addedFormats'][$format_name]))
			throw new ExcelExporterException("Format '$format_name' returned by formatCallback does not exist");
		return $params['addedFormats'][$format_name];
	}
}

?>