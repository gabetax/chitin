<?php
/**
 * TextHelper
 *
 * @author Sean McCann <sean@mudbugmedia.com>
 * @copyright Mudbug Media, 2008-10-21
 * @version $Id:$
 * @package Chitin
 * @subpackage Helpers
 */

class TextHelperException extends Exception { }

/** 
 * Provides methods for various string formatting operations.
 * @link https://wiki.mudbugmedia.com/index.php/TextHelper
 * @todo extend Inflector ???
 */ 
class TextHelper {
	/**
	 * @var array List of modes accepted by truncate()
	 */
	protected static $truncate_modes = array('smart','exact');
	protected static $search_modes = array('smart','exact');
	
	/**
	 * Shortens long passages of text.
	 * If 'text' is longer than 'length', return up to the first 'length' characters in 'text', followed by 'append_string'.
	 *
	 * Required parameters:
	 * - text
	 *
	 * Optional parameters:
	 * - length: the maximum number of characters present in our shortened version of 'text'.  Default: 100
	 * - append_string: the text that replaces the truncated portion of 'text'.  Default: '...'
	 * - mode:  changes the truncating behavior (see below).  Default: 'smart'
	 *
	 * Truncation Modes
	 * - exact: truncates a string to exactly a certain number of characters.
	 *          This is most useful for shortening data streams or other 
	 *          binary data (e.g. "ATTGCAATACCGCAC")
	 * - smart: truncates a string so that it will be *no longer than* x 
	 *          number of characters.  Smart mode takes into account word
	 *          endings and punctuation, ensuring that phrases are not
	 *          awkwardly trimmed. This is the default behavior for truncate()
	 *          Note:  it is possible that this mode truncates the text down to
	 *          an empty string, in which case, the append string will not be
	 *          attached.
	 */
	public static function truncate ( $params ) {
		// Test for required parameters
		if (!isset($params['text'])) {
			throw new TextHelperException("'text' parameter is required for TextHelper::truncate");
		}
		
		// Setup our defaults and merge them with passed parameters
		$default_params = array (
			'text' => '',
			'length' => 100,
			'append_string' => '...',
			'mode' => 'smart',
		);
		$params = array_merge($default_params, $params);
		
		// Check for other conditions that will cause this method to fail
		if (!is_numeric($params['length']) || $params['length'] < 0) {
			throw new TextHelperException("'length' parameter for TextHelper::truncate must be a positive integer");
		}

		if (!in_array($params['mode'], self::$truncate_modes)) {
			throw new TextHelperException("Unrecognized 'mode' provided to TextHelper::truncate.  Accepted modes are: " . implode(',', self::$truncate_modes));
		}
		
		// If there is nothing to truncate, return the original text
		if (mb_strlen($params['text']) <= $params['length']) {
			return $params['text'];
		}
		
		$matches = array();
		$patterns = array();
		$patterns['smart'] = "/(^.{0," . $params['length'] . "})\b/";
		$patterns['exact'] = "/^(.{" . $params['length'] . "})/";
		
		// Test for a bad expression
		if (!preg_match($patterns[$params['mode']], $params['text'], $matches)) {
			throw new TextHelperException("Unexpected pattern matching failure in TextHelper::truncate\n* Pattern: " . $patterns[$params['mode']] . "\n* Text: " . $params['text']);
		}
		$truncated_text = $matches[1];
		
		// Kill off unwanted endings in smart mode
		if ($params['mode'] == 'smart') {
			$truncated_text = preg_replace("/\W*(\s\w+-)*$/", '', $truncated_text);
		}

		// Add the append_string.  Since it's possible for smart mode
		// to truncate the text down to '', only add the append_string
		// if we still have some text.
		if (mb_strlen($truncated_text) > 0) {
			$truncated_text .= $params['append_string'];
		}
		return $truncated_text;
	}
	
	/**
	 * Pulls out an excerpt of text of a specified radius on both sides of a
	 * provided search string.
	 *
	 * If 'phrase' is not contained in 'text', return TextHelper::truncate with
	 * length of twice the radius
	 *
	 * Required parameters:
	 * - text: The string that will be the subject of the search.
	 * - phrase: The case-insensitive string to search for.
	 *
	 * Optional parameters:
	 * - radius: the maximum number of characters to pull from the left and right of the matched string.  Default: 100
	 * - append_string: the text that replaces the truncated portions of 'text'.  Default: '...'
	 * - mode:  changes the excerpt behavior (see below).  Default: 'smart'
	 *
	 * Truncation Modes
	 * - exact: truncates a string to exactly a certain number of characters.
	 *          This is most useful for shortening data streams or other 
	 *          binary data (e.g. "ATTGCAATACCGCAC")
	 * - smart: truncates a string so that it will be *no longer than* x 
	 *          number of characters.  Smart mode takes into account word
	 *          endings and punctuation, ensuring that phrases are not
	 *          awkwardly trimmed. This is the default behavior for excerpt()
	 *
	 * @param array $params
	 * @return string
	 */
	public static function excerpt ($params) {
		// Test for required parameters
		if (!isset($params['text'])) {
			throw new TextHelperException("'text' parameter is required for TextHelper::excerpt");
		}

		if (!isset($params['phrase'])) {
			throw new TextHelperException("'phrase' parameter is required for TextHelper::excerpt");
		}
		
		// Setup our defaults and merge them with passed parameters
		$default_params = array (
			'radius' => 100,
			'append_string' => '...',
			'mode' => 'smart'
		);
		$params = array_merge($default_params, $params);
		
		// Check for other conditions that will cause this method to fail
		if (!is_numeric($params['radius']) || $params['radius'] < 0) {
			throw new TextHelperException("'radius' parameter for TextHelper::excerpt must be a positive integer");
		}

		if (!in_array($params['mode'], self::$truncate_modes)) {
			throw new TextHelperException("Unrecognized 'mode' provided to TextHelper::excerpt.  Accepted modes are: " . implode(',', self::$truncate_modes));
		}
		
		// Find if the search string goes past the beginning or end of the line.
		$through_beginning = false;
		$through_end = false;
		$match_location = stripos($params['text'], $params['phrase']);
		if ($match_location <= $params['radius']) {
			$through_beginning = true;
		}
		if (strlen($params['text']) - ($match_location + strlen($params['phrase'])) <= $params['radius']) {
			$through_end = true;
		}
		
		$matches = array();
		$patterns = array();
		
		//For smart searches, we need to make sure we don't lose beginning or ending punctuation if the radius
		$patterns['smart'] = "/";
		if (!$through_beginning) {
			$patterns['smart'] .= "\b";
		}
		$patterns['smart'] .= "(.{0," . $params['radius'] . "}" . preg_quote($params['phrase'], '/') . ".{0," . $params['radius'] . "})";
		if (!$through_end) {
			$patterns['smart'] .= "\b";
		}
		$patterns['smart'] .= "/i";
		
		
		$patterns['exact'] = "/(.{0," . $params['radius'] . "}" . preg_quote($params['phrase'], '/') . ".{0," . $params['radius'] . "})/i";
		
		// If we don't have a match return a truncated string twice the radius length
		if (preg_match($patterns[$params['mode']], $params['text'], $matches) == 0) {
			return TextHelper::truncate(array(
				'text' => $params['text'],
				'mode' => $params['mode'],
				'append_string' => $params['append_string'],
				'length' => $params['radius'] * 2,
				));
		}
		
		$excerpted_text = $matches[1];

		// Kill off unwanted endings in smart mode
		if ($params['mode'] == 'smart') {
			if (!$through_end) {
				$excerpted_text = preg_replace("/\W*(\s\w+-)*$/", '', $excerpted_text);
			}
			if (!$through_beginning) {
				$excerpted_text = preg_replace("/^(\s\w+-)*\W*/", '', $excerpted_text);
			}
		}

		if (!$through_end) {
			$excerpted_text = $excerpted_text . $params['append_string'];
		}
		if (!$through_beginning) {
			$excerpted_text = $params['append_string'] . $excerpted_text;
		}
		
		return $excerpted_text;
	}
	
	
	/**
	 * Wraps the text that's found with a specific tag or wrapper.
	 * Find all instances of 'phrases' within a 'text', and wrap them with the specified 'highlighter' strong
	 *
	 * Required parameters:
	 * - text: String - The string that will be the subject of the search.
	 * - phrases: mixed - The case-insensitive strings to search for.
	 *
	 * Optional parameters:
	 * - highlighter: The string that will wrap the phrases that are found.  Must contain '\1' as a 
	 *                place holder for where the replaced text should be put back in.  Default: '<strong class="highlight">\1</strong>'
	 * - mode:  changes the excerpt behavior (see below).  Default: 'smart'
	 *
	 * Search Modes
	 * - exact: Searches for each entry in phrases anywhere, including inside of
	 *          words.  All matches will have the highlighter performed.
	 * - smart: Searches for each entry in phrases only at word boundaries.  This means that
	 *          matches that are inside of words will not have the highlighter applied.
	 */
	function highlight ( $params ) {
		// Test for required parameters
		if (!isset($params['text'])) {
			throw new TextHelperException("'text' parameter is required for TextHelper::highlight");
		}

		if (!isset($params['phrases'])) {
			throw new TextHelperException("'phrases' parameter is required for TextHelper::highlight");
		}
		
		// Setup our defaults and merge them with passed parameters
		$default_params = array (
			'highlighter' => '<strong class="highlight">\1</strong>',
			'mode' => 'smart',
			'case_sensitive' => false
		);
		$params = array_merge($default_params, $params);
		
		// Check for other conditions that will cause this method to fail.
		if (!is_string($params['highlighter'])) {
			throw new TextHelperException("'highlighter' parameter must be a string for TextHelper::highlight");
		}	
			
		if (preg_match('/\\\1/', $params['highlighter']) == 0) {
			throw new TextHelperException("'highlighter' parameter must contain a '\1' to denote where the searched text will be put back into the string for TextHelper::highlight");
		}		

		if (!in_array($params['mode'], self::$search_modes)) {
			throw new TextHelperException("Unrecognized 'mode' provided to TextHelper::search.  Accepted modes are: " . implode(',', self::$search_modes));
		}
		
		if (!is_array($params['phrases']) && !is_string($params['phrases'])) {
			throw new TextHelperException("'phrases' parameter must be either an array or a string for TextHelper::highlight");
		}
		// Below, we will expect $params['phrases'] to be an array.  If it's not one, let's make it one now.
		elseif (!is_array($params['phrases'])) { 
			$phrases = Array();
			$phrases[] = strval($params['phrases']);
			$params['phrases'] = $phrases;
		}
		
		// Set up the patters that we will attempt to match.
		$phrases = Array();
		$i = 0;
		foreach ($params['phrases'] as $phrase) {
			$i++;
			if ($params['mode'] == 'smart') {
				$phrases[$i] = '/\b'.preg_quote($phrase, '/').'\b/';
			}
			else {
				$phrases[$i] = '/'.preg_quote($phrase, '/').'/';
			}
			if (!$params['case_sensitive']) {
				$phrases[$i] .= 'i';
			}
		}
		
		// Replace the highlighter 'replace' marker with one that is more correct in php.
		$highlighter = preg_replace('/\\\1/', '\${0}', $params['highlighter']);
		
		return preg_replace($phrases, $highlighter, $params['text']);
	}
}
?>