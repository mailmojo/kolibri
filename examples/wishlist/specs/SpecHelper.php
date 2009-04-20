<?php
/**
 * Loads all the correct files and mode for KolibriTestCase
 *
 * REMEMBER to require this file in every spec class you have
 * require_once(dirname(__FILE__) . '/../TestBootstrap.php')
 */



/*
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
require_once(ROOT . '/core/Config.php');
Config::getInstance();


require_once(ROOT . '/specs/fixtures/Fixtures.php');
require_once(ROOT . '/specs/KolibriTestCase.php');
?>