<?php
/**
 * The HTTP gateway to the Kolibri framework. Essential core files are included, config
 * initialized and processing of the HTTP request is started.
 * The constants defined here, ROOT and APP_PATH, can be changed from their default values by
 * using SetEnv in the .htaccess file or in an appropriate Apache config directive.
 */

/*
 * Defines the root directory of the Kolibri framework. By default this is a directory named
 * 'kolibri' within the document root.
 */
if ($rootDir = getenv('KOLIBRI_ROOT')) {
	define('ROOT', $rootDir);
}
else {
	define('ROOT', dirname(__FILE__) . '/kolibri');
}

/*
 * Defines the root directory for the application. By default this is the same directory as
 * this kolibri.php file.
 */
if ($appDir = getenv('KOLIBRI_APP')) {
	define('APP_PATH', $appDir);
}
else {
	define('APP_PATH', dirname(__FILE__));
}

// Require essential files. Others are loaded as needed.
require(ROOT . '/core/Config.php');
require(ROOT . '/core/RequestProcessor.php');

// Init configuration
Config::getInstance();

$request = new Request($_GET, $_POST);
$kolibri = new RequestProcessor($request);
$kolibri->process();
?>
