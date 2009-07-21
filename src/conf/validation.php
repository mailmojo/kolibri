<?php
/*
 * Defines validator constants, used to define rules on model properties. They are mapped to concrete
 * validator classes below.
 */
define('EXISTS', 1);
define('UNIQUE', 2);
define('MATCHES', 4);
define('IS_NUM', 8);
define('IS_TEXT', 16);
define('IS_ALPHANUM', 32);
define('IS_EMAIL', 64);
define('IS_URL', 128);
define('IS_DATE', 256);
define('IS_DATETIME', 512);
define('IS_HEX', 1024);
define('IS_FILE', 2048);
define('IN_DB', 4096);

/*
 * Maps validation rules to validator classes used to perform the validation.
 */
$validators = array(
	EXISTS      => 'ExistsValidator',
	UNIQUE      => 'UniqueValidator',
	MATCHES     => 'MatchesValidator',
	IS_NUM      => 'NumberValidator',
	IS_TEXT     => 'TextValidator',
	IS_ALPHANUM => 'AlphaNumValidator',
	IS_EMAIL    => 'EmailValidator',
	IS_URL      => 'UrlValidator',
	IS_DATE     => 'DateValidator',
	IS_DATETIME	=> 'DateTimeValidator',
	IS_HEX      => 'HexValidator',
	IS_FILE     => 'FileValidator',
	IN_DB       => 'InDbValidator'
);

/*
 * Messages the different validators throw upon errors. Uses standard sprintf() placeholders.
 */
$validationMessages = array(
	'exists'        => '%s is required.',
	'match'         => '%s must match %s.',
	'unique'        => '%s «%s» already exists.',
	'num'           => '%s must be a number.',
	'size'          => '%s must be between %s and %s.',
	'minsize'       => '%s can\'t be below than %s.',
	'maxsize'       => '%s can\'t be above then %s.',
	'alphanum'      => '%s can only contain the letters A-Z, numbers, underscore and dash.',
	'text'          => '%s can only contain regular text.',
	'email'         => '%s must be a valid e-mail address.',
	'length'        => '%s must consist of exactly %d characters.',
	'minlength'     => '%s can\'t consist of less than %d characters.',
	'maxlength'     => '%s can\'t consist of more than %d characters.',
	'url'           => '%s must be a valid URL.',
	'date'          => '%s must be a valid date.',
	'hex'           => '%s must be a hexidecimal number.',
	'file'          => '%s must be a file.',
	'file_ini_size' => '%s exceeds the maximum allowed file size.',
	'file_partial'  => '%s wasn\'t completely uploaded. Please try again.',
	'type'          => '%s must be one of the following file types: %s',
	'in_db'         => '%s «%s» doesn\'t exist.'
);
?>
