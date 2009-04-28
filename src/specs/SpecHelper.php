<?php
/**
 * Loads all the correct files and mode for KolibriTestCase
 *
 * REMEMBER to require this file in every spec class you have
 * <code>require_once(dirname(__FILE__) . '/../SpecHelper.php')</code>
 * 
 * Defines the root directory of the Kolibri framework. By default this is a directory named
 * 'kolibri' within the document root.
 */
if (!defined('ROOT')) {
	if (!$rootDir = getenv('KOLIBRI_ROOT')) {
		throw new Exception('Environment variable KOLIBRI_ROOT must be defined.');
		exit;
	}
	define('ROOT', $rootDir);

	/*
	 * Defines the root directory for the application. By default this is the same directory as
	 * this kolibri.php file.
	 */
	$dirname = dirname(__FILE__);
	if (basename($dirname) == 'specs') {
		$path = dirname(__FILE__) . '/..';
	}
	else $path = dirname(__FILE__) . '/../..';

	define('APP_PATH', $path);
}

putenv('KOLIBRI_MODE=test');
require(ROOT . '/core/Config.php');
require(ROOT . '/core/RequestProcessor.php');

Config::getInstance();

require(ROOT . '/specs/Fixtures.php');
require(ROOT . '/specs/KolibriContext.php');

$setupFile = APP_PATH . '/specs/setup.sql';
$schemaFile = APP_PATH . '/config/schema.sql';

if (file_exists($setupFile)) {
	$db = DatabaseFactory::getConnection();
	
	if (file_exists($schemaFile)) {
		$schemaContents = file_get_contents($schemaFile);
		$db->batchQuery($schemaContents);
		$db->commit();
	}
	
	$setupContents = file_get_contents($setupFile);
	$db->batchQuery($setupContents);
	$db->commit();
}
?>
