<?php
/**
 * Helper functions related to URLs.
 * $Id: urls.php 1513 2008-06-21 12:16:05Z anders $
 */

/**
 * Converts a string to pure ASCII characters for use in URLs. Different from urlencode in that it
 * doesn't encode non-ASCII characters as %dd but translates them into simpler ASCII characters.
 */
function urlify ($string) {
	$translations = array(
		' & '	=> '+',
		' - '	=> '+',
		' '		=> '+',
		'?'		=> '',
		'&' 	=> '+',
		','		=> '',
		':'		=> '',
		'"'		=> '',
		'#' 	=> '',
		'%' 	=> ''
	);
	
	/*
	 * Since PHP 8.2 strtolower() only lowercases ASCII, so it is safe on UTF-8 input and the
	 * former round trip through ISO-8859-1 is no longer needed.
	 */
	return strtr(strtolower($string), $translations);
}
?>
