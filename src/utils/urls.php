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
    $iso_8859_1 = utf8_decode($string);
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
	
	return utf8_encode(strtr(strtolower($iso_8859_1), $translations));
}
?>
