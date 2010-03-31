<?php
/**
 * Configuration variables
 *
 * This file is included by init.php, thus its array is available on
 * every dynamic page.  When referencing this array, you should refer to it as
 * $GLOBALS['config']
 *
 * @author    Gabe Martin-Dempesy <gabe@mudbugmedia.com>
 * @copyright Copyright &copy; 2005 Mudbug Media
 * @package   Chitin
 * @version   $Id: config.php 3802 2009-04-08 20:40:51Z gabebug $
 */

// Configuration Options
$config = array();

$config['site_name'] = "My Site";

/**
 * This variable is set in the profile
 * @var string DSN formatted for PEAR's DB
 * @see http://pear.php.net/manual/en/package.database.db.intro-dsn.php
 */
#$config['dsn'] = "mysqli://username:password@localhost/database_name";

/**
 * Unix Zoneinfo file
 *
 * Setting this will update the entire environment as well as MySQL connections
 * Leaving this unset will default to /etc/localtime
 */
//$config['time_zone'] = 'US/Central';

/**
 * Layout Files
 */
$config['layouts'] = array();
$config['layouts']['normal'] = 'layouts/normal.php';

/**
 * Default options for use with the Chitin Pager class
 *
 * @link https://wiki.mudbugmedia.com/index.php/Chitin_Pager
 */
$config['pager'] = array();
