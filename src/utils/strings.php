<?php
/**
 * Helper functions related to strings.
 * @version 	$Id: strings.php 1532 2008-07-31 16:20:51Z frode $
 */

/**
 * Translates characters that have a special meaning in XML to it's safe equivalent.
 * 
 * @param string $string	The text to escape.
 * @param array $exclude	Characters that should be excluded from the standard list of
 *							characters to escape.
 * @return string			The text with special characters escaped.
 */
function xml_escape ($string, $exclude = null) {
	$trans_tbl = array();
	$trans_tbl['<'] = '&lt;';
	$trans_tbl['>'] = '&gt;';
	$trans_tbl['"'] = '&quot;';
	$trans_tbl["'"] = '&#39;';
	$trans_tbl['&'] = '&amp;';

	if (is_array($exclude)) {
		foreach ($exclude as $char) {
			if (isset($trans_tbl[$char])) unset($trans_tbl[$char]);
		}
	}

	return strtr($string, $trans_tbl);
}

/**
 * Translates special XML entities to their plain text equivalents.
 * 
 * @param string $string	The text to convert.
 * @param array $exclude	XML-entities to exclude from the conversion.
 * @return string			The text with any XML entities converted to plain text.
 */
function xml_decode ($string, $exclude = null) {
	$trans_tbl = array();
	$trans_tbl['&lt;'] = '<';
	$trans_tbl['&gt;'] = '>';
	$trans_tbl['&quot;'] = '"';
	$trans_tbl['&#39;'] = "'";
	$trans_tbl['&amp;'] = '&';

	if (is_array($exclude)) {
		foreach ($exclude as $entity) {
			if (isset($trans_tbl[$entity])) unset($trans_tbl[$entity]);
		}
	}

	return strtr($string, $trans_tbl);
}

/**
 * Converts line breaks from one style to another (i.e. from Windows- to Unix style).
 *
 * @param string $string	String to convert line breaks in.
 * @return string			String with converted line breaks.
 */
function convert_linebreaks ($string, $to = "\n") {
	if ($to == "\n") {
		$linebreak_translations = array(
			"\r\n"	=> "\n",
			"\r"	=> "\n"
		);			
	}
	else if ($to == "\r") {
		$linebreak_translations = array(
			"\r\n"	=> "\r",
			"\n"	=> "\r"
		);
	}
	else if ($to == "\r\n") {
		$linebreak_translations = array(
			"\n"	=> "\r\n",
			"\r"	=> "\r\n"
		);
	}
	else {
		return false;
	}

	return strtr($string, $linebreak_translations);
}

/**
 * Converts text from one encoding to another. The function tries to determine the source
 * encoding if one is not defined. The default target encoding is UTF-8.
 *
 * @param string $string	Text to encode.
 * @param string $to		Encoding to convert to. UTF-8 is the default.
 * @param string $from		Encoding to convert from. If not defined, try to detect it.
 * @return string	Text converted to correct encoding.
 */
function convert_encoding ($string, $to = 'UTF-8', $from = null) {		
	// Do we have to detect source encoding?
	if ($from === null) {
		if (!is_utf8($string)) {
			// Get the first three bytes for BOM-tests
			$byte[0] = ord(mb_substr($string, 0, 1));
			$byte[1] = ord(mb_substr($string, 1, 1));
			$byte[2] = ord(mb_substr($string, 2, 1));
			$byte[3] = ord(mb_substr($string, 3, 1));

			if ($byte[0] == 0 && $byte[1] == 0 &&
					$byte[2] == 255 && $byte[3] == 254) { // UTF-32LE
				$from = 'UTF-32LE';
			}
			else if ($byte[0] == 0 && $byte[1] == 0 &&
					 $byte[2] == 254 && $byte[3] == 255) { // UTF-32BE
				$from = 'UTF-32BE';
			}
			else if ($byte[0] == 255 && $byte[1] == 254) { // UTF-16LE
				$from = 'UTF-16LE';
			}
			else if ($byte[0] == 254 && $byte[1] == 255) { // UTF-16BE
				$from = 'UTF-16BE';
			}
			else {
				// Place ISO prior to UTF-8, else ISO-text was detected as UTF-8...
				$from = mb_detect_encoding($string, 'ISO-8859-1, UTF-8');

				/*
				 * If `mb_detect_encoding` thinks it is UTF-8, it is more likely UTF-16 as
				 * our is_utf8()-test didn't hit.
				 */
				if ($from == 'UTF-8') {
					$from = 'UTF-16';
				}
			}
		}
		else {
			$from = 'UTF-8';
		}
	}

	return ($to == $from ? $string : mb_convert_encoding($string, $to, $from));
}

/**
 * Checks if a string is valid UTF-8.
 *
 * @param string $string	The string to check.
 * @return boolean	<code>TRUE</code> if the string is encoded as UTF-8, <code>FALSE</code> if not.
 */
function is_utf8 ($string) {
	// Obviously, an empty string is valid UTF-8
	if (strlen($string) == 0) {
		return true;
	}

	/*
	 * If even just the first character can be matched, when the /u modifier is used, then it's valid
	 * UTF-8. If the UTF-8 is somehow invalid, nothing at all will match, even if the string contains
	 * some valid sequences.
	 */
	return (preg_match('/^.{1}/us', $string) == 1);

	// Possible improvement of the regex, that does not allow UTF-8 conformant sequences not allowed
	// in the Unicode-standard:
	// return (preg_match('/[\xC0\xC1\xF5-\xFF]/u', $value) == 0);
}

/**
 * Creates a random string composed of the following ASCII characters:
 *     48-57  = '0' - '9'
 *     65-90  = 'A' - 'Z'
 *     97-122 = 'a' - 'z'
 *
 * @param int $length	Length of the string to generate. Defaults to 6 chars.
 * @return string
 */
function random_string ($length = 6) {
	$string = '';

	$exclude = array(58, 59, 60, 61, 62, 63, 64, 91, 92, 93, 94, 95, 96);
	while (strlen($string) < $length) {
		$asciiCode = rand(48, 122);
		if (!in_array($asciiCode, $exclude)) {
			$string .= chr($asciiCode);
		}
	}

	return strtoupper($string);
}
?>
