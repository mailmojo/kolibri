<?php
/**
 * PHPSpec
 * This file will run all specs within this directory and all child-directories.
 * TODO safeguard sjekke at mode = test
 */


chdir('specs');

require_once 'PHPSpec.php';

/*
 * We start the session here because the action tests are randomly executed
 * and in some cases the session has not been initialized before the output.
 */
session_start();

$options = new stdClass;
$options->recursive = true;
$options->specdoc = true;
$options->reporter = 'html';

ob_start();

PHPSpec_Runner::run($options);

ob_end_flush();

?>