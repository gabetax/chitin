<?php
/**
 * @author Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @version $Id: BaseRow.php 5212 2009-10-05 21:05:25Z gabebug $
 * @copyright Mudbug Media, 2007-10-15
 * @package Chitin
 * @subpackage Models
 */

include_once 'DBSingleton.php';

class BaseRowException extends Exception { }
class BaseRowQueryException extends BaseRowException { }
class BaseRowRecordNotFoundException extends BaseRowException { }

/**
 * BaseRow is an abstract class implementing an ORM
 *
 * @link https://wiki.mudbugmedia.com/index.php/BaseRow
 * @package Chitin
 * @subpackage Models
 */
abstract class BaseRow {

	/**
	 * @var PEAR::DB
	 */
	 protected $dao;

	/**
	 * @var string Optional database name used to override the default selected database supplied by DBSingleton
	 */
	 protected $database_name;

	/**
	 * @var string Name of the table whose rows we are modelling
	 */
	 protected $table_name;
	
	/**
	 * @var string field name of the primary key. Defaults to 'id'
	 */
	 protected $primary_key_field = 'id';

	/**
	 * @var array field names
	 */
	 protected $fields;

	/**
	 * @var array
	 */
	protected $protected_fields = array('updated', 'updated_on', 'updated_at', 'created', 'created_on', 'created_at');
	protected $updated_fields = array('updated', 'updated_on', 'updated_at');
	protected $created_fields = array('created', 'created_on', 'created_at');
	

	/**
	 * @var mixed String or array of strings of field names to apply to find() operations if no order_by or sort_ parameter were provided.
	 */
	protected $default_sort_fields;

	/**
	 * @var mixed String or array of strings of field directions (ASC or DESC) to compliment $default_sort_fields
	 */
	protected $default_sort_directions;
	
	/**
	 * @var string Default 'order_by' parameter to apply to find() operations if no order_by or sort_ parameter were provided.
	 */
	protected $default_order_by;

	/**
	 * @var integer When save() or validateRecursive(), the number of table associations deep to process
	 */
	protected $default_recurse = 1;

	/**
	 * @var array The currently represented row in an associative array
	 */
	protected $row;

	/**
	 * @var array List of errors
	 */
	protected $errors;
	
	/**
	 * @var boolean Is the data in the current row saved?
	 */
	protected $saved;

	/**
	 * @var boolean Does this instance already exist in the database?
	 */
	protected $new_record;

	/**
	 * @param mixed Hash of BaseRow associations, where the key is the public field name by which we will reference the data
	 */
	protected $associations = array();
	
	/**
	 * @param mixed Hash of named scope rules, where the key is the name and the value is a find parameter
	 *
	 * Example:
	 * array('available' => array('where' => 'status = 1'));
	 * FooTable()->scope('available')->find();
	 */
	 protected $scopes;
	
	/**
	 * @param mixed Array of find() rules or named scope strings. DO NOT MODIFY (this should be private, but unserialize complains about private vars)
	 * @ignore
	 */
	protected $scope_stack = array();

	/**
	 * Instantiates a new row from an array of data
	 */
	function __construct ($row = null) {
		// Instance variables
		$this->row = (is_null($row) ? array() : $row);
		if (!is_array($this->row)) throw new BaseRowException("Unexpected input provided to BaseRow::__construct.  Expected: array or null, received: " . gettype($this->row));
		$this->saved = false;
		$this->new_record = true;
		
		// Call the 'setup' method if it exists. This method should act as a constructor that doesn't have to super or deal with argument passing
		if (method_exists($this, 'setup')) $this->setup();
	}


	/*
	 *
	 * STATIC METHODS
	 *
	 */
	
	/**
	 * Run a query and return all the results as an array of rows
	 * @todo Log errors instead of sending a notice
	 * @access protected
	 * @param string $query SQL query to run
	 * @param array $params Optional prepare() parameters.  If null or not passed, it will not be sent
	 * @return array of rows
	 */
	 protected function _query ($query, $params = null) {
		if (!isset($this->dao))
			$this->dao = DBSingleton::getInstance();
		
		if (class_exists('ChitinLogger')) {
			ChitinLogger::log(get_class($this) . ": " . $query);
			if (!is_null($params) && count($params) > 0)
				ChitinLogger::log('Params: ' . var_export($params, true));
		}
	
		$result = (is_null($params) ?
			$this->dao->getAll($query) :
			$this->dao->getAll($query, $params));
		
		if (PEAR::isError($result))
			throw new BaseRowQueryException($result->getUserInfo());

		return $result;
	}

	/**
	 * Return the last inserted ID
	 * @access protected
	 * @return PRIMARY_KEY
	 */
	 protected function _insertID () {
		$query = "SELECT LAST_INSERT_ID() AS id";
		$result = $this->_query($query);
		return $result[0]['id'];
		
	}

	/**
	 * Return an escaped table name, including database name if set
	 *
	 * Example: `pureftpd`.`users`
	 *
	 * @access protected
	 * @return string
	 */
	 protected function _tableStatement () {
		return (isset($this->database_name) ?
			'`'.$this->database_name.'`.`'.$this->table_name.'`' :
			'`'.$this->table_name.'`');
	}

	/**
	 * Fetches and caches a list of fields/columns from the database
	 * @access protected
	 */
	 protected function _fetchfields () {
		// If something is already set or cached, don't re-fetch
		if (!is_null($this->fields))
			return;

		if (!isset($this->dao))
			$this->dao = DBSingleton::getInstance();

		// PEAR DB's tableInfo() does not properly escape table names,
		// so you can run into some problems when a table name is a
		// reserved word in MySQL (e.g. "group").
		//
		// The PEAR DB maintainers does not intend to change this
		// behavior as is evidenced from the comments on Bugzilla:
		//
		// @see http://pear.php.net/bugs/bug.php?id=8336
		//   [2006-08-01 13:06 UTC] lsmith (Lukas Smith)
		//   IIRC Daniel decided that in PEAR::DB it will be left to the user to
		//   quote identifiers passed to tableInfo()....
		//
		// To get around this issue, we will escape our table name.  However,
		// we do consider it bad practice at Mudbug Media to name tables using
		// reserved words.
		$table_info = $this->dao->tableInfo($this->_tableStatement());
		if (PEAR::isError($table_info))
			throw new BaseRowQueryException($table_info->getUserInfo());
		
		foreach ($table_info as $row_info)
			$this->fields[$row_info['name']] = $row_info;
	}
	
	/**
	 * Determine if this model has a requested association, and return the
	 * association type
	 *
	 * @param string $field
	 * @return string Association type of the field ('has_one', 'has_many', 'belongs_to', 'many_to_many')
	 */
	protected function _hasAssociation ($field) {
		$relations = array('has_one', 'has_many', 'belongs_to', 'many_to_many');
		foreach ($relations as $r)
			if (isset($this->{$r}[$field]))
				return $r;
		return false;
	}
	
	/**
	 * Save data into the database
	 *
	 * The $data array will be filtered through to only include fields that exist in this table
	 * 
	 * @access protected
	 * @param string $type Type of query, INSERT, UPDATE, or REPLACE
	 * @param array $data Associative array of data
	 * @param string $where Optional where clause to limit when updating
	 * @param array $params_append Optional params array to use with your $where clause
	 */
	protected function _save ($type, $data, $where = null, $params_append = null) {
		
		// Make sure the given $type is one we can actually do
		$valid_types = array('INSERT', 'UPDATE', 'REPLACE');
		if (!in_array($type, $valid_types)) {
			throw new BaseRowException("BaseRow::_save: $type is not a valid save type");
		}
		
		$this->_fetchfields();
		
		// Find the intersection of $data and the fields
		$pairs = array();
		$params = array();
		foreach ($data as $field => $value) {
			// Add it if, 1) It exists in the field list, 2) it isn't in the list of automatically generated fields
			if (isset($this->fields[$field])) {
				if (isset($this->wrapper_fields[$field])) {
					$pairs[]  = "`$field` = " . sprintf($this->wrapper_fields[$field], '!');
					$params[] = $value;
				} else if (!in_array($field, $this->protected_fields) || ($type == 'REPLACE' && $field == $this->primary_key_field)) {
					$pairs[]  = "`$field` = ?";
					$params[] = $value;
				}
			}
		}

		// Automatically add the timestamp of any field in the
		// $created_field list if it appears in our field list
		if ($type != 'UPDATE') {
			foreach ($this->created_fields as $field) {
				if (isset($this->fields[$field]))
					$pairs[] = "`$field` = NOW()";
			}
		}
		
		foreach ($this->updated_fields as $field) {
			if (isset($this->fields[$field]))
				$pairs[] = "`$field` = NOW()";
		}
		

		// Update nothing if we have no fields to update
		if (count($pairs) < 1)
			return null;

		// UPDATE statements do not need the INTO keyword
		$into = ($type == 'UPDATE') ? '' : 'INTO ';

		$query = "$type $into" . $this->_tableStatement() . " SET ";
		$query .= implode( ", \n", $pairs);
		if ($type == 'UPDATE' && !is_null($where)) {
			$query .= "\nWHERE $where";
		}
		
		if (!is_null($params_append))
			$params = array_merge($params, $params_append);

		return $this->_query($query, $params);
	}


	/**
	 * Condenses extraneous "magic" find parameters down to the core values used by find:
	 * - callback
	 * - select
	 * - where
	 * - group_by
	 * - having
	 * - limit
	 * - order_by
	 * - params
	 */
	protected function _findCondense ($params) {
		$condensed = array();

		$condensed['callback'] = (isset($params['callback'])) ? $params['callback'] : null;
		$condensed['select']   = (isset($params['select'])) ? $params['select'] : null;
		$condensed['joins']    = (isset($params['joins'])) ? $params['joins'] : null;
		list($condensed['where'], $condensed['params']) = $this->_findWhere($params);
		$condensed['group_by'] = (isset($params['group_by'])) ? $params['group_by'] : null;
		$condensed['having']   = (isset($params['having'])) ? $params['having'] : null;
		$condensed['order_by'] = $this->_findOrderBy($params);
		$condensed['limit']    = $this->_findLimit($params);
		
		return $condensed;
	}

	/**
	 * Determines the default SELECT clause based on find parameters:
	 * @access protected
	 * @param array $params Supplied parameters.  This is not inspected by default
	 * @return string "*, ..."
	 */
	protected function _findSelect ($params) {
		if (isset($params['select']))
			return $params['select'];
		
		$this->_fetchfields();
		$select = "*";

		foreach ($this->created_fields as $field)
			if (in_array($field, $this->fields))
				$select .= ", UNIX_TIMESTAMP($field) AS $field";
		foreach ($this->updated_fields as $field)
			if (in_array($field, $this->fields))
				$select .= ", UNIX_TIMESTAMP($field) AS $field";
		return $select;
	}

	/**
	 * Determines the ORDER BY clause based on find parameters:
	 * @access protected
	 * @param array $params Supplied parameters, the following of which are inspected:
	 * - where
	 * - params
	 * - fields
	 * - values
	 * - match_any
	 * @return array (WHERE clause, prepare() params aray), e.g. array('name = ? and state = ?', array('Scott', 'LA'))
	 */
	 protected function _findWhere ($params) {
		$this->_fetchfields();
		
		if (!isset($params['params']))
			$params['params'] = array();
			
		// Build the WHERE clause if we don't already have one supplied
		if (!isset($params['where'])) {
			
			// If there was no 'where' or 'fields' parameter, we can't build anything
			if (!isset($params['fields']) && !isset($params['values']))
				return array(null, array());
			
			// Iterate through if the fields are an array
			if (is_array($params['fields'])) {
				$pieces = array();
				for ($i = 0; $i < count($params['fields']); $i++)  {
					if (isset($this->fields[$params['fields'][$i]])) {
						$pieces[] = $this->_findWhereSegment($params['fields'][$i], $params['values'][$i]);
						$params['params'][] = $params['values'][$i];
					}
				}
				
				$operator = (isset($params['match_any']) ? ' OR ' : ' AND ');
				$params['where'] = implode($operator, $pieces);
				
			} else {
				if (isset($this->fields[$params['fields']])) {
					$params['where'] = $this->_findWhereSegment($params['fields'], $params['values']);
					$params['params'] = array($params['values']);
				} else
					return array(null, array());
			}
		}
		
		return array($params['where'], $params['params']);
	}

	/**
	 * Build a params-style sub-where clause for matching a given field and value
	 *
	 * This method is extracted to abstract the operator for dealing with null values
	 *
	 * @access private
	 * @param string field name
	 * @param string value to match
	 * @return string
	 */
	private function _findWhereSegment ($field, $value) {
		return $this->_tableStatement() . ".`$field` " . (is_null($value) ? 'IS' : '=') . " ?";
	}
	
	/**
	 * Determines the ORDER BY clause based on find parameters:
	 * @access protected
	 * @param array $params Supplied parameters, the following of which are inspected:
	 * - sort_fields
	 * - sort_directions
	 * - order_by
	 * @return string e.g. "field_1 ASC, field2_DESC", or null if a valid statement is not set
	 */
	 protected function _findOrderBy ($params) {
		
		// order_by parameter receives top priority, and is not sanitized
		if (isset($params['order_by']))
			return $params['order_by'];

		if (!isset($params['sort_fields']))
			return null;

		$pieces = array();
		if (is_array($params['sort_fields'])) {
			for ($i = 0; $i < count($params['sort_fields']); $i++)
				if (isset($this->fields[$params['sort_fields'][$i]]))
					$pieces[] = $this->_tableStatement().'.`'.$params['sort_fields'][$i] . '` ' . ((isset($params['sort_directions'][$i]) && strcasecmp($params['sort_directions'][$i], 'DESC') == 0) ? 'DESC' : 'ASC');
		} else if (isset($this->fields[$params['sort_fields']])) {
			$pieces[] = $this->_tableStatement().'.`'.$params['sort_fields'] . '` ' . ((isset($params['sort_directions']) && strcasecmp($params['sort_directions'], 'DESC') == 0) ? 'DESC' : 'ASC');
		}

		if (count($pieces) == 0)
			return null;
		else
			return join($pieces, ', ');
	}


	/**
	 * Return an ORDER BY clause to be used if nothing has been supplied by scope() or find()
	 * @return string e.g. "field_1 ASC, field2_DESC", or null if a valid statement is not set
	 */
	protected function _findDefaultOrderBy () {
		if (isset($this->default_order_by))
			return $this->default_order_by;
		if (isset($this->default_sort_fields) && isset($this->default_sort_directions)) {
			return $this->_findOrderBy(array(
				'sort_fields' => $this->default_sort_fields,
				'sort_directions' => $this->default_sort_directions,
			));
		} else
			return null;
		
	}

	/**
	 * Determines the LIMIT clause based on find parameters:
	 * @access protected
	 * @param array $params Supplied parameters, the following of which are inspected:
	 * - limit_start - Which record number to 
	 * - page - Alternative to 'limit_start'; Which "page number" to start on, assuming the first page is '1'.
	 * - per_page - How many results per page do we display.  If omitted, defaults to 15
	 * - first - If set, we only want the very first.  Returns '0, 1';
	 * @return string e.g. "0, 15", or null if a start is not passed
	 */
	protected function _findLimit ($params) {
		// This only returns the very first instance.  Override the limit so we aren't wasting time
		if (isset($params['first']))
			return '0, 1';
			
		// Enforce that everything is a number
		if (isset($params['page'])        && !preg_match('/^\d+$/', $params['page'])) $params['page'] = 1;
		if (isset($params['limit_start']) && !preg_match('/^\d+$/', $params['limit_start'])) $params['limit_start'] = 0;
		if (!isset($params['per_page'])   || !preg_match('/^\d+$/', $params['per_page'])) $params['per_page'] = 15;
		
		if (!isset($params['page']) && !isset($params['limit_start']))
			return null;

		if (isset($params['page']) && !isset($params['limit_start']))
			$params['limit_start'] = ($params['page'] - 1) * $params['per_page'];
		
		return $params['limit_start'] . ', ' . $params['per_page'];
	}
	
	
	/**
	 * Instantiates the row, runs callbacks, and sets it as existing
	 * @access protected
	 * @param array $row Associative array
	 * @param boolean $callback Run the callback if it's implemented?
	 * @return BaseRow 
	 */
	 protected function _findInstantiate ($row, $callback = true) {
		$obj = new $this($row);
		$obj->saved = true;
		$obj->new_record = false;
		if ($callback && method_exists($this, 'callbackAfterFetch'))
			$obj->callbackAfterFetch();
		return $obj;
	}
	
	/**
	 * Merges two pre-condensed find() param arrays together, with the right side taking dominance
	 *
	 * This is used to resolve a scope() chain
	 *
	 * @param mixed $left 
	 * @param mixed $right
	 * @return mixed Find param()
	 */
	protected function _findMergeParams($left, $right) {
		
		// These params are merged together if they both exist via sprintf. $1 = right, $2 = left
		$merges = array(
			'select'   => '%s, %s',
			'joins'    => "%s\n%s",
			'where'    => '(%2$s) AND (%1$s)',
			'group_by' => '%s, %s',
			'having'   => '(%2$s) AND (%1$s)',
			'order_by' => '%s, %s',
			);
		foreach ($merges as $key => $sprintf)
			if (isset($left[$key]) && isset($right[$key])) // R && L
				$right[$key] = sprintf($sprintf, $right[$key], $left[$key]);
			else if (!isset($right[$key]) && isset($left[$key])) // !R && L
				$right[$key] = $left[$key];
			// R && !L requires no work.

		// params are merged too, but they are arrays, so we can't use sprintf to do the merging
		$right['params'] = array_merge($left['params'], $right['params']);

		// These params will have the right parameter overwrite the left if it exists
		$overwrites = array(
			'limit',
			'callback'
			);
		foreach ($overwrites as $key)
			if (!isset($right[$key]) && isset($left[$key]))
			$right[$key] = $left[$key];
			
		return $right;
	}
	
	protected function _namedScopeResolve ($param) {
		if (is_array($param))
			return ($param);
		else if (is_string($param) && isset($this->scopes[$param]))
			return $this->scopes[$param];
		else
			throw new BaseRowException("Could not resolve scope rule: " . var_export($param, true));
		
	}
	
	/**
	 * Merge find() parameters into future find() calls or association references
	 *
	 * @param mixed find() array
	 * @see find
	 * @return BaseRow
	 */
	public function scope ($rules) {
		$new = clone $this;
		
		// Unset any cached associations.  That way if we do $row->scope(..)->association, it will be forced to fetch a fresh copy with the scope enforced
		foreach (array_keys($this->associations) as $assoc)
			unset($new->$assoc);
		
		// Bottom of the stack is the front of the array.  This is opposite of what array_push/pop do, but it's easier to iterate top down
		array_unshift($new->scope_stack, $rules);
		
		return $new;
	}
	
	/**
	 * Return the scope stack
	 *
	 * This is primarily of utility of the BaseRowAssociation objects, and you shouldn't ever need to directly call this yourself
	 *
	 * @internal
	 * @return array stack of scope() calls
	 */
	public function getScopeStack () {
		return $this->scope_stack;
	}
	
	/**
	 * Sets the scope stack from a previous getScopeStack() call
	 * @internal
	 * @param mixed $stack Stack from getScopeStack()
	 */
	public function setScopeStack ($stack) {
		$this->scope_stack = $stack;
	}
	
	/**
	 * Return the SELECT query for the given scope and parameters
	 * @param array $params Analogous to find()
	 * @return array 0 => query, 1 => scope-condensed array of find() parameters including query 'params'
	 */
	public function getFindQuery ($params = array()) {
		$condensed = $this->_findCondense($params);

		foreach ($this->scope_stack as $scope)
			$condensed = $this->_findMergeParams($this->_findCondense($this->_namedScopeResolve($scope)), $condensed);
		
		// Populate default values if nothing was provided in $params or scope()
		if (is_null($condensed['select']))   $condensed['select']   = $this->_findSelect($params);
		if (is_null($condensed['order_by'])) $condensed['order_by'] = $this->_findDefaultOrderBy($params);
		
		$query = "SELECT " . $condensed['select'] . "\nFROM " . $this->_tableStatement();
		if (!is_null($condensed['joins']))    $query .= "\n"          . $condensed['joins'];
		if (!is_null($condensed['where']))    $query .= "\nWHERE "    . $condensed['where'];
		if (!is_null($condensed['group_by'])) $query .= "\nGROUP BY " . $condensed['group_by'];
		if (!is_null($condensed['having']))   $query .= "\nHAVING "   . $condensed['having'];
		if (!is_null($condensed['order_by'])) $query .= "\nORDER BY " . $condensed['order_by'];
		if (!is_null($condensed['limit']))    $query .= "\nLIMIT "    . $condensed['limit'];
		
		return array($query, $condensed);
	}
	
	/**
	 * Locate rows based on supplied search criteria
	 * @access public
	 * @static (pending LSB in 5.3)
	 * @param array Named parameters:
	 * - string   'where' WHERE clause to return results by. Value will not be sanitized prior to querying.
	 * - array    'params' array of prepare() parameters to use in conjunction with a 'where' clause
	 * - mixed    'fields' field name(s) to limit results by in conjunction with passed value(s).  When passed as an array, by default all values must match
	 * - mixed    'values' value(s) to match with the above field(s)
	 * - boolean  'match_any' When matching an array of fields/values, return ANY results to match (default: false)
	 * - mixed    'sort_fields' field(s) to sort results by.  Values which are not fields in this table will be skipped.
	 * - mixed    'sort_directions' Direction(s) to sort results by, 'ASC' or 'DESC'.  Defaults to 'ASC' if invalid or not set
	 * - string   'order_by' Complete ORDER BY clause.  Value will not be sanitized prior to querying.
	 * - int      'per_page' Maximum number of results to return. Defaults to '15' if 'limit_start' is set
	 * - int      'page' Automatically sets the 'limit_start' property with the assistance of 'per_page'
	 * - int      'limit_start' Record index to start a LIMIT statement with
	 * - boolean  'first' If set, only returns the first row of the results instead of an array
	 * - boolean  'callback' Run callbackAfterFetch if it's present? Default: true
	 * @return array array of BaseRows
	 */
	public function find ($params = array()) {
		list ($q, $condensed) = $this->getFindQuery($params);

		$rows = $this->_query($q, $condensed['params']);

		// _query had an error.  That's probably our fault.
		if (is_null($rows)) {
			// var_dump($query, $query_params);
			return null;
		}
		
		// Instantiate
		$instances = array();
		foreach ($rows as $row)
			$instances[] = $this->_findInstantiate($row, (!isset($condensed['callback']) || $condensed['callback']));
		
		if (isset($params['first']))
			return isset($instances[0]) ? $instances[0] : null;
			
		return $instances;
	}
	
	/**
	 * Fetch a BaseRow instance that matches the passed primary key
	 *
	 * @access public
	 * @static (pending LSB in 5.3)
	 * @param PRIMARY_KEY $id Value of the row's primary key
	 * @return BaseRow
	 */
	 public function get ($id) {
		$record = $this->find(array('fields' => $this->primary_key_field, 'values' => $id, 'first' => true));
		if (is_null($record))
			throw new BaseRowRecordNotFoundException("Could not locate " . get_class($this) . " record #" . $id);
		return $record;
	}

	/**
	 * Determine if a particular row exists in the table
	 *
	 * @access public
	 * @static (pending LSB in 5.3)
	 * @param PRIMARY_KEY $id Value of the row's primary key
	 * @return boolean
	 */
	 public function exists ($id) {
		return ($this->rowCount("`{$this->primary_key_field}` = ?", array($id)) > 0);
	}

	/**
	 * Determine number of rows in a table for a provided where clause
	 *
	 * If no clause is provided, the total row count will be returned.
	 *
	 * This method can be scoped with the scope() function
	 *
	 * @access public
	 * @static (pending LSB in 5.3)
	 * @param string $where Optional WHERE clause to use
	 * @param array $params Optional prepare() style params array
	 * @return integer row count
	 */
	public function rowCount ($where = null, $params = array()) {
		$condensed = array('where' => $where, 'params' => $params);
		foreach ($this->scope_stack as $scope)
			$condensed = $this->_findMergeParams($this->_findCondense($this->_namedScopeResolve($scope)), $condensed);

		$query = "SELECT COUNT(*) as `count` FROM " . $this->_tableStatement();
		if (!empty($condensed['where']))
			$query .= " WHERE " . $condensed['where'];
		
		$rows = $this->_query($query, $condensed['params']);
		return $rows[0]['count'];
	}

	/** 
	 * TRUNCATE the entire table
	 *
	 * This will remove all the rows from the table and reset the auto increment counter
	 * @access public
	 * @static (pending LSB in 5.3)
	 */
	public function truncate () {
		$query = "TRUNCATE " . $this->_tableStatement();
		$this->_query($query);
	}

	/**
	 * Delete a single row by provided id
	 * @access public
	 * @static (pending LSB in 5.3)
	 * @param PRIMARY_KEY $id
	 */
	public function delete ($id) {
		return $this->deleteAllBySQL('`'.$this->primary_key_field . "` = ?", array($id));
	}

	/**
	 * Delete all rows matching a WHERE clause with provided data
	 * @access public
	 * @static (pending LSB in 5.3)
	 * @param string $where Optional WHERE clause to use
	 * @param array $params Optional prepare() style params array
	 */
	public function deleteAllBySQL ($where = null, $params = null) {
		$query = "DELETE FROM "  . $this->_tableStatement();
		if (!is_null($where)) 
			$query .= " WHERE $where";
			
		$this->_query($query, $params);
	}
	
	/**
	 * Update a single row with provided data
	 *
	 * Because a partial row can be passed, no validation is run on this data
	 *
	 * @access public
	 * @static (pending LSB in 5.3)
	 * @param array $data Associative array of row data
	 * @param PRIMARY_KEY $id ID to update
	 */
	public function update ($data, $id) {
		return $this->updateAllBySQL($data, '`'.$this->primary_key_field . "` = ?", array($id));
	}
	
	/**
	 * Update all rows matching a WHERE clause with provided data
	 *
	 * Because a partial row can be passed, no validation is run on this data
	 *
	 * @access public
	 * @static (pending LSB in 5.3)
	 * @param array $data Associative array of row data
	 * @param string $where Optional WHERE clause matches which rows to update
	 * @param array $params Optional prepare() parameters
	 */
	 function updateAllBySQL ($data, $where = null, $params = null) {
		$this->_save('UPDATE', $data, $where, $params);
	}
	
	/**
	 * Run a query and return all the results as an array of rows
	 *
	 * This is a public wrapper for the protected _query
	 *
	 * @param string $query SQL query to run
	 * @param array $params Optional prepare() parameters.  If null or not passed, it will not be sent
	 * @return array of rows
	 */
	public function query ($query, $params = null) {
		return $this->_query($query, $params);
	}
	
	
	/*
	 *
	 * INSTANCE METHODS
	 *
	 */
	
	/**
	 * PHP overloaded function to set the value of a field
	 *
	 * Example usage: $myrow->name = "stuff" will call $myrow->__set('name', 'stuff');
	 * @access public
	 * @param string $field Name of field to set
	 * @param mixed $value Value to assign
	 */
	public function __set ($field, $value) {
		$this->saved = false;
		$this->row[$field] = $value;
	}
	
	/**
	 * PHP overloaded function to set the value of a field
	 *
	 * Note that this method returns by reference. This allows us to modify
	 * properties whose values are arrays, such as with associations.
	 *
	 * Example usage: $myrow->name will call $myrow->__get('name');
	 * @access public
	 * @param string $field Name of field to fetch
	 * @return mixed
	 */
	public function &__get ($field) {
		// If it's an existing property, give what we have on hand
		if (isset($this->row[$field]))
			return $this->row[$field];
		
		
		// Check to see if the field is an association that hasn't been fetched yet
		if (!isset($this->associations[$field])) {
			// Return by reference mandates a variable to refer to.
			$null = null;
			return $null;
		}
		
		$this->row[$field] = $this->associations[$field]->fetch($this);
		return $this->row[$field];
	}

	/**
	 * PHP overloaded function to determine if a value has been assigned
	 *
	 * Example usage: isset($myrow->name) will call $myrow->__isset('name');
	 * @access public
	 * @param string $field Name of field to check
	 * @return boolean
	 */
	public function __isset ($field) {
		return isset($this->row[$field]) || (isset($this->associations[$field]));
	}
	
	/**
	 * PHP overloaded function to unassign a particular field
	 *
	 * Example usage: unset($myrow->name) will call $myrow->__unset('name');
	 * @access public
	 * @param string $field Name of field to unset
	 */
	public function __unset ($field) {
		$this->saved = false;
		unset($this->row[$field]);
	}
	
	/**
	 * Removes the database when serialize()ing
	 * @access public
	 */
	public function __sleep () {
		// __sleep requires an array of variable names to keep. Why can't it just be a trigger?
		$vars = get_class_vars(__CLASS__);
		unset($vars['dao']);
		return array_keys($vars);
	}
	
	/**
	 * Return the value of the primary key of this row, if it exists
	 * @return integer
	 */
	public function id () {
		return $this->__get($this->primary_key_field);
	}
	
	/**
	 * Merge the contents of passed array with the current data
	 * @access public
	 * @param array $data
	 */
	public function merge ($data) {
		$this->saved = false;
		$this->row = array_merge($this->row, $data);
	}
	
	/**
	 * Return the row's data as an associative array
	 * @access public
	 * @return array
	 */
	public function toArray () {
		return $this->row;
	}
	
	/**
	 * Is this row already existing in the database?
	 * @access public
	 * @return boolean
	 */
	public function isNew () {
		return $this->new_record;
	}
	
	/**
	 * Is the current state of this row, along with any changes, saved in the database?
	 * @return boolean
	 */
	public function isSaved() {
			return $this->saved;
	}

	/**
	 * Validates data and constructs an associative array of error messages
	 * @access public
	 * @param string $type Type of validation: 'INSERT' or 'UPDATE'
	 * @return array List of error messages
	 */
	public function validate ($type = 'INSERT') {
		return array();
	}

	/**
	 * Recursively call validate()
	 * @param mixed $params 
	 * @return array List of error messages
	 */
	public function validateRecursive ($params = array()) {
		if (!isset($params['recurse'])) $params['recurse'] = $this->default_recurse;
		$errors = array();

		if ($params['recurse'] > 0) {
			$params['recurse']--;
			
			$relations = array('HasOne', 'HasMany', 'BelongsTo', 'ManyToMany');
			foreach ($this->associations as $name => $rules)
				if (isset($this->row[$name]))
					if (is_array($this->row[$name]))
						for ($i = 0; $i < count($this->row[$name]); $i++)
							$errors += $this->_validateRecursive_prefixKeys($this->row[$name][$i]->validateRecursive($params), $name.'_'.$i.'_');
					else if ($this->row[$name] instanceof BaseRow) {
						$errors += $this->_validateRecursive_prefixKeys($this->row[$name]->validateRecursive($params), $name . '_');
					}
		}

		$errors += $this->validate(($this->isNew() ? 'INSERT' : 'UPDATE'));
		return $errors;
	}
	
	/**
	 * Prefix every key in an array with a string
	 * @param array $array Array to prefix
	 * @param string $prefix Prefix to apply to strings
	 * @return array
	 */
	protected function _validateRecursive_prefixKeys ($array, $prefix) {
		$ret = array();
		foreach ($array as $key => $value)
			$ret[$prefix . $key] = $value;
		return $ret;
	}

	/**
	 * Returns errors from a previous validation run
	 * @access public
	 * @return array of error messages
	 */
	public function getErrors () {
		return $this->errors;
	}

	/**
	 * Refresh the data straight from the database
	 *
	 * This will rerun any callbacks that occur during normal instantiation
	 * @access public
	 */
	public function reload () {
		$row = $this->get($this->__get($this->primary_key_field));
		$this->row = $row->toArray();
		//$this->merge($row->toArray());
		$this->saved = true;
	}

	/**
	 * Saves the data into the database
	 *
	 * This will run an INSERT for a new row, or an UPDATE for an existing row
	 * @param mixed $params Associative array of parameters:
	 * - integer 'recurse' How many layers deep to save associations.  If "0", no associations will be saved. Default: 1
	 * - boolean 'validate' Validate the row before saving. If disabled, this may result in errors. Default: true
	 * @access public
	 */
	public function save ($params = array()) {
		// Setup $params
		if (!isset($params['recurse'])) $params['recurse'] = $this->default_recurse;
		if (!isset($params['validate']))   $params['validate'] = true;
		
		if ($params['validate']) {
			$this->errors = $this->validateRecursive(array('recurse' => $params['recurse']));
			if (count($this->errors))
				return false;
		}

		// Associated rows: save all instantiated associations whose ID's are needed for this row
		$this->_saveAssoc(array('BelongsTo'), $params);
		
		// Save this particular row if it isn't already saved
		if (!$this->saved) {
			$type = ($this->isNew() ? 'INSERT' : 'UPDATE');

			// Run the 'beforeSave' callback
			if (method_exists($this, 'callbackBeforeSave'))
				$this->callbackBeforeSave();
		
			// Build and run the query
			if ($type == 'UPDATE')
				$this->_save($type, $this->row, "`{$this->primary_key_field}` = ?", array($this->row[$this->primary_key_field]));
			else
				$this->_save($type, $this->row);
		
			// Set primary key
			if ($type == 'INSERT')
				$this->__set($this->primary_key_field, $this->_insertID());

			$this->saved = true;
			$this->new_record = false;
		}

		// Associated rows: save all instantiated associations that needed this row's id
		$this->_saveAssoc(array('HasOne', 'HasMany', 'ManyToMany'), $params);
		
		return true;
	}
	
	/**
	 * Forward requests to save associated BaseRows
	 * @param array $types Array of association types that we should attempt to save now, e.g. array('BelongsTo', 'HasMany');
	 * @param array $params Parameters for save()
	 */
	 
	protected function _saveAssoc ($types, $params) {
		if ($params['recurse'] < 1)
			return;

		$params['recurse']--;
		foreach ($this->associations as $name => $rules) {
			if (isset($this->row[$name]) && in_array(get_class($rules), $types))
				$rules->save($this, $name, $params);
		}
	}
}


abstract class BaseRowAssociation {
	protected $rules;
	
	public function __construct ($rules) {
		if (!is_array($rules)) 
			throw new BaseRowException("BaseRow Association rules must be an array");
		$this->rules = $rules;
	}
	
	/**
	 * Fetch and return the row(s) for this association rule
	 * @param BaseRow $row Parent row
	 * @return mixed BaseRow(s)
	 */
	abstract public function fetch ($row);
	
	/**
	 * Save the row(s) for this association rule
	 * @param BaseRow $row Parent row
	 * @param string $name Field Name of this association rule
	 * @param mixed $param Parameters passed to BaseRow::save()
	 */
	abstract public function save ($row, $name, $params);
	
	public function __get ($key) {
		if (isset($this->rules[$key]))
			return $this->rules[$key];
		else
			return null;
	}
	
	public function __set ($key, $value) {
		$this->rules[$key] = $value;
	}
	
	protected function getTable ($row) {
		$table = new $this->class;
		$table->setScopeStack($row->getScopeStack());
		return $table;
	}
}

class BelongsTo extends BaseRowAssociation {
	public function fetch ($row) {
		return $this->getTable($row)->get($row->{$this->key});
	}
	public function save ($row, $name, $params) {
		if ($row->$name instanceof BaseRow) {
			$row->$name->save($params);
			$row->{$this->key} = $row->$name->id();
		}
	}
}

class HasMany extends BaseRowAssociation {
	public function fetch ($row) {
		return $this->getTable($row)->find(array('fields' => $this->key, 'values' => $row->id));
	}
	public function save ($row, $name, $params) {
		if (is_array($row->$name)) {
			// Assume that this row is already saved, and update the associated row with our ID
			for ($i = 0; $i < count($row->$name); $i++) {
				if ($row->{$name}[$i] instanceof BaseRow) {
					$row->{$name}[$i]->{$this->key} = $row->id();
					$row->{$name}[$i]->save($params);
				}
			}
		} else if ($row->name instanceof BaseRow) {
			$row->$name->{$this->key} = $row->id();
			$row->$name->save($params);
		}
	}
}

class HasOne extends HasMany {
	public function fetch ($row) {
		return $this->getTable($row)->find(array('fields' => $this->key, 'values' => $row->id, 'first' => true));
	}
}

class ManyToMany extends BaseRowAssociation {
	public function fetch ($row) {
		return $this->getTable($row)->find(array('where' => "id IN (SELECT {$this->remote_key} FROM {$this->table} WHERE {$this->local_key} = ?)", 'params' => array($row->id)));
	}
	public function save ($row, $name, $params) {
		// delete all rows
		$row->query("DELETE FROM `!` WHERE `!` = ?", array($this->table, $this->local_key, $row->id()));
		// insert matching
		for ($i = 0; $i < count($row->$name); $i++) {
			if ($row->{$name}[$i] instanceof BaseRow) {
				$row->{$name}[$i]->save($params);
				$row->query("REPLACE INTO `!` SET `!` = ?, `!` = ?", array(
					$this->table,
					$this->local_key,
					$row->id(),
					$this->remote_key,
					$row->{$name}[$i]->id()));
			}
		}
		
	}
}

?>
