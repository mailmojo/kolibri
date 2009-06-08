<?php
/**
 * This helper file loads all the correct files and prepares Kolibri for testing.
 *
 * IMPORTANT! Remember to copy this file to your applications /specs directory, and require
 * it in every spec class you have:
 * <code>require_once(dirname(__FILE__) . '/../SpecHelper.php')</code>
 */

/*
 * Defines the root directory of the Kolibri framework. For testing we can't easily guess where
 * the framework is installed, so we throw an exception if KOLIBRI_ROOT environment variable
 * is not set.
 */
if (!defined('ROOT')) {
	if (!$rootDir = getenv('KOLIBRI_ROOT')) {
		throw new Exception('Environment variable KOLIBRI_ROOT must be defined.');
		exit;
	}
	define('ROOT', $rootDir);

	/*
	 * Defines the root directory for the application. By default this is above the directory
	 * of this file (meaning above /specs), unless the KOLIBRI_APP environment variable is
	 * set.
	 */
	if (!$dirname = getenv('KOLIBRI_APP')) {
		$dirname = dirname(__FILE__) . '/..';
	}
	define('APP_PATH', $dirname);
}

// Initialize Kolibri in test mode
putenv('KOLIBRI_MODE=test');
require(ROOT . '/core/Config.php');
require(ROOT . '/core/RequestProcessor.php');
Config::getInstance();

require(ROOT . '/specs/Fixtures.php');
require(ROOT . '/specs/KolibriContext.php');

// Make the following variables global so prepare_database() can be called from other contexts
global $setupFile, $schemaFile;
$setupFile  = APP_PATH . '/specs/setup.sql';
$schemaFile= APP_PATH . '/conf/schema.sql';

prepare_database();

/**
 * Function to initialize the database if a setup and optionally schema file is present.
 * This is defined as a function in order to set up the database in a consistent state for
 * each integration test, where simple rollback() like we otherwise do won't suffice (as
 * integration tests are run through an external browser).
 */
function prepare_database () {
	global $setupFile, $schemaFile;

	if (file_exists($setupFile)) {
		$db = DatabaseFactory::getConnection();

		/*
		 * If a setup SQL file is defined, we also support the use of a general application
		 * schema file to set up the database.
		 */
		if (file_exists($schemaFile)) {
			$schemaContents = file_get_contents($schemaFile);
			$db->batchQuery($schemaContents);
			$db->commit();
		}

		$setupContents = file_get_contents($setupFile);
		$db->batchQuery($setupContents);
		$db->commit();
	}
}
