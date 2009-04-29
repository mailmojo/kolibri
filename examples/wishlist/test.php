<?php
/**
 * This file will run all specs for your application, with pretty HTML output.
 */

// If not KOLIBRI_APP environment variable is set, we set it to the current directory.
if (!$appDir = getenv('KOLIBRI_APP')) {
	putenv('KOLIBRI_APP=' . dirname(__FILE__));
}
chdir(getenv('KOLIBRI_APP') . '/specs');
require('PHPSpec.php');

/*
 * We start the session here because the action tests are randomly executed and in some
 * cases the session has not been initialized before the output (which in turn gives an error
 * when a session is later initialized).
 */
session_start();

$options = new stdClass;
$options->recursive = true;
$options->specdoc   = true;
$options->reporter  = 'html';

ob_start();
PHPSpec_Runner::run($options);
ob_end_flush();
?>
