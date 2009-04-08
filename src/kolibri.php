<?php
/**
 * The entryway to the Kolibri framework. Essential core files are included, config initialized and
 * processing of the request is started.
 */

/*
 * Defines the root directory of the framework. By default this in a directory named kolibri within the 
 * document root, but this can be changed at will.
 */
define('ROOT', dirname(__FILE__));

/*
 * Application specific directories. Modify to your setup if different from the default.
 */
//define('APP_PATH', dirname(__FILE__));
define('APP_PATH', '/Users/frode/Sites/wishlist');
define('ACTIONS_PATH', APP_PATH . '/actions');
define('MODELS_PATH', APP_PATH . '/models');
define('VIEW_PATH', APP_PATH . '/views');

// Require essential files. Others are loaded as needed.
require(ROOT . '/core/Config.php');
require(ROOT . '/core/RequestProcessor.php');

// Init configuration
Config::getInstance();

$kolibri = new RequestProcessor();
$kolibri->process();
?>
