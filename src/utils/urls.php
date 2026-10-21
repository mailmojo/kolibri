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
	 * Drop characters outside ISO-8859-1, as the former round trip through that encoding did,
	 * and invalid UTF-8 bytes. Since PHP 8.2 strtolower() only lowercases ASCII, so it is safe
	 * on the remaining UTF-8 input.
	 */
	$latin1 = preg_replace('/[^\x{00}-\x{FF}]/u', '', $string);
	if ($latin1 === null) {
		$latin1 = preg_replace('/[\x80-\xFF]/', '', $string);
	}
	return strtr(strtolower($latin1), $translations);
}
?>
